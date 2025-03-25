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

class EvaluateDebateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $debateId) {}

    public function handle(): void
    {
        try {
            Log::info('ディベート評価ジョブを開始', ['debate_id' => $this->debateId]);

            $debate = Debate::with(['messages.user', 'affirmativeUser', 'negativeUser', 'room'])
                ->findOrFail($this->debateId);

            // AI評価サービスを呼び出し、評価データを取得
            $evaluationData = AIEvaluationService::evaluate($debate);

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

                // 評価完了イベントをブロードキャスト
                broadcast(new DebateEvaluated(
                    $debate->id
                ));
            });
        } catch (\Exception $e) {
            Log::error('ディベート評価処理中にエラーが発生', [
                'debate_id' => $this->debateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->fail($e);
        }
    }
}
