<?php

namespace App\Jobs;

use App\Models\Debate;
use App\Events\TurnAdvanced;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class AdvanceDebateTurnJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $debateId;
    public $expectedTurn;
    /**
     * Create a new job instance.
     */
    public function __construct($debateId, $expectedTurn)
    {
        $this->debateId = $debateId;
        $this->expectedTurn = $expectedTurn;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $debate = Debate::find($this->debateId);
        $room = $debate->room;

        if (!$debate || $room->status !== 'debating') {
            // ディベートが存在しない、またはディベート中でない場合は処理を終了
            return;
        }

        // 現在のターンが期待されるターンと一致するか確認、Manuallyとのバッティングを防止
        if ($debate->current_turn !== $this->expectedTurn) {
            return;
        }

        $next_turn = $debate->getNextTurn();

        if ($next_turn) {
            // 次のターンを設定
            $debate->current_turn = $next_turn;
            $debate->turn_end_time = Carbon::now()->addSeconds(Debate::$turns[$next_turn]['duration']);
            $debate->save();

            // TurnAdvanced イベントをブロードキャスト
            broadcast(new TurnAdvanced($debate))->toOthers();

            // 次のターン終了時にこのジョブを再スケジュール
            AdvanceDebateTurnJob::dispatch($debate->id, $next_turn)->delay($debate->turn_end_time);
        } else {
            // ディベート終了
            $room->status = 'finished';
            $debate->turn_end_time = null;
            $debate->save();

            // 結果評価などの処理をあとで追加

            // 結果通知イベントをブロードキャスト
            // broadcast(new DebateFinished($debate))->toOthers();
        }
    }
}
