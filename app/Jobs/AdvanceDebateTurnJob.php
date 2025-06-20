<?php

namespace App\Jobs;

use App\Models\Debate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Services\DebateService;

/**
 * ターン終了後に次のターンへ自動的に進行させるジョブ
 */
class AdvanceDebateTurnJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $debateId, public int $expectedTurn)
    {
        //
    }

    /**
     * expectedTurnが一致するなら次のターンへ進める
     */
    public function handle(DebateService $debateService): void
    {
        try {
            Log::info('ターン進行ジョブ開始', [
                'debate_id' => $this->debateId,
                'expected_turn' => $this->expectedTurn
            ]);

            $debate = Debate::find($this->debateId);
            if (!$debate) {
                Log::warning('ディベートが見つかりません', ['debate_id' => $this->debateId]);
                return;
            }

            $debateService->advanceToNextTurn($debate, $this->expectedTurn);
        } catch (\Exception $e) {
            Log::error('ターン進行処理でエラーが発生しました', [
                'debate_id' => $this->debateId,
                'expected_turn' => $this->expectedTurn,
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            // 再試行が必要なエラーの場合は例外を再スロー
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }

    /**
     * ジョブ失敗時の処理
     */
    public function failed(?Throwable $exception, DebateService $debateService): void
    {
        Log::critical('ターン進行ジョブが失敗しました', [
            'debate_id' => $this->debateId,
            'expected_turn' => $this->expectedTurn,
            'error' => $exception ? $exception->getMessage() : '不明なエラー'
        ]);

        // 終了処理を安全に実行
        try {
            $debate = Debate::find($this->debateId);
            if ($debate && $debate->room && $debate->room->status === 'debating') {
                $debateService->terminateDebate($debate);
            }
        } catch (\Exception $e) {
            Log::error('ターン進行失敗後の終了処理でエラーが発生しました', [
                'debate_id' => $this->debateId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
