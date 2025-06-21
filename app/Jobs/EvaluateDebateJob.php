<?php

namespace App\Jobs;

use App\Models\Debate;
use App\Models\DebateEvaluation;
use App\Services\AIEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Events\DebateEvaluated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class EvaluateDebateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 10;
    public $timeout = 300;

    public function __construct(private int $debateId)
    {
        //
    }

    public function handle(AIEvaluationService $aiEvaluationService): void
    {
        try {
            Log::info('ディベート評価ジョブを開始', ['debate_id' => $this->debateId]);

            $debate = Debate::with(['messages.user', 'affirmativeUser', 'negativeUser', 'room'])
                ->findOrFail($this->debateId);

            // AI評価サービスを呼び出し、評価データを取得
            $evaluationData = $aiEvaluationService->evaluate($debate);

            DB::transaction(function () use ($debate, $evaluationData) {
                // 評価結果をDBに保存
                $evaluation = DebateEvaluation::updateOrCreate(
                    ['debate_id' => $debate->id],
                    $evaluationData
                );

                Log::info('ディベート評価完了', [
                    'debate_id' => $this->debateId,
                    'winner' => $evaluationData['winner']
                ]);

                // トランザクション成功後に評価完了イベントをブロードキャスト
                DB::afterCommit(function () use ($debate) {
                    try {
                        broadcast(new DebateEvaluated($debate->id));
                    } catch (\Exception $e) {
                        Log::error('評価完了イベントのブロードキャストに失敗しました', [
                            'debate_id' => $this->debateId,
                            'error' => $e->getMessage()
                        ]);
                    }
                });
            });
        } catch (\Exception $e) {
            Log::error('ディベート評価処理中にエラーが発生', [
                'debate_id' => $this->debateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 最大試行回数に達していない場合は再試行
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }

    /**
     * ジョブ失敗時の処理
     */
    public function failed(?Throwable $exception): void
    {
        Log::critical('ディベート評価ジョブが失敗しました', [
            'debate_id' => $this->debateId,
            'error' => $exception ? $exception->getMessage() : '不明なエラー'
        ]);

        // 代替の簡易評価を作成して保存
        try {
            $debate = Debate::find($this->debateId);
            if ($debate) {
                // 簡易評価データを作成
                $fallbackEvaluation = [
                    'debate_id' => $this->debateId,
                    'winner' => null,
                    'summary' => '技術的な問題により評価を完了できませんでした。',
                    'affirmative_feedback' => '評価処理中にエラーが発生しました。',
                    'negative_feedback' => '評価処理中にエラーが発生しました。',
                ];

                DebateEvaluation::updateOrCreate(
                    ['debate_id' => $this->debateId],
                    $fallbackEvaluation
                );

                // 評価完了イベントを送信
                broadcast(new DebateEvaluated($this->debateId));
            }
        } catch (\Exception $e) {
            Log::error('ディベート評価失敗後の代替処理でエラーが発生しました', [
                'debate_id' => $this->debateId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
