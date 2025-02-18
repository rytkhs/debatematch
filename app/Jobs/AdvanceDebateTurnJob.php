<?php

namespace App\Jobs;

use App\Models\Debate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * ターン終了後に次のターンへ自動的に進行させるジョブ
 */
class AdvanceDebateTurnJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $debateId;
    public int $expectedTurn;
    /**
     * Create a new job instance.
     */
    public function __construct(int $debateId, int $expectedTurn)
    {
        $this->debateId = $debateId;
        $this->expectedTurn = $expectedTurn;
    }

    /**
     * expectedTurnが一致するなら次のターンへ進める
     */
    public function handle(): void
    {
        $debate = Debate::find($this->debateId);
        if (!$debate) {
            return;
        }

        $debate->advanceToNextTurn($this->expectedTurn);
    }
}
