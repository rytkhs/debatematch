<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Debate;
use Illuminate\Support\Facades\Auth;
use App\Events\TurnAdvanced;
use App\Jobs\AdvanceDebateTurnJob;
use Carbon\Carbon;

class DebateInfo extends Component
{
    public $debate;
    public $current_turn;
    public $turn_name;
    public $next_turn_name;
    public $turn_duration;
    public $turn_speaker;
    public $turn_end_time;
    public $current_speaker;


    public function mount(Debate $debate)
    {
        $this->debate = $debate;
        $this->current_turn = $debate->current_turn;
        $this->turn_name = Debate::$turns[$debate->current_turn]['name'];
        $this->turn_duration = Debate::$turns[$debate->current_turn]['duration'];
        $this->turn_end_time = $debate->turn_end_time->timestamp ?? 0;
        $this->next_turn_name = Debate::$turns[$debate->current_turn + 1]['name'] ?? '終了';
        $this->current_speaker = Debate::$turns[$this->current_turn]['speaker'];
    }
    public function getListeners()
    {
        return [
            "echo:debate.{$this->debate->room_id},TurnAdvanced" => 'updateTurn',
        ];
    }

    public function updateTurn($event)
    {
        $this->current_turn = $event['current_turn'];
        $this->turn_name = $event['turn_name'];
        $this->turn_duration = $event['turn_duration'];
        $this->turn_end_time = $event['turn_end_time'];
        $this->current_speaker = Debate::$turns[$this->current_turn]['speaker'];
    }

    public function advanceTurnManually()
    {
        $user = Auth::user();
        // 現在のスピーカーが肯定側か否定側かを確認
        // $isAffirmative = ($this->current_speaker === 'affirmative' && $this->affirmative_user_id === $user->id);
        // $isNegative = ($this->current_speaker === 'negative' && $this->negative_user_id === $user->id);

        // if (!($isAffirmative || $isNegative)) {
        //     session()->flash('error', 'あなたは現在のターンを進める権限がありません。');
        //     return;
        // }
        // ディベートを最新状態で再取得
        // $this->debate = Debate::find($this->debate->id);

        // 現在のターンを取得
        $currentTurn = $this->debate->current_turn;

        //最新のdebateを取得
        $this->debate = Debate::find($this->debate->id);

        // 次のターンを取得
        $next_turn = $this->debate->getNextTurn();

        if ($next_turn) {
            // 次のターンを設定
            $this->debate->current_turn = $next_turn;
            $this->debate->turn_end_time = Carbon::now()->addSeconds(Debate::$turns[$next_turn]['duration']);
            $this->debate->save();

            // TurnAdvanced イベントをブロードキャスト
            broadcast(new TurnAdvanced($this->debate));

            // 次のターンに対するジョブをスケジュール
            AdvanceDebateTurnJob::dispatch($this->debate->id, $next_turn)->delay($this->debate->turn_end_time);
        } else {
            // ディベート終了処理
            $this->debate->room->status = 'finished';
            $this->debate->turn_end_time = null;
            $this->debate->save();

            // broadcast(new DebateFinished($this->debate))->toOthers();
        }
        // フロントエンドの状態を更新
        $this->updateTurn([
            'current_turn' => $this->debate->current_turn,
            'turn_name' => Debate::$turns[$this->debate->current_turn]['name'],
            'turn_duration' => Debate::$turns[$this->debate->current_turn]['duration'],
            'turn_speaker' => Debate::$turns[$this->debate->current_turn]['speaker'],
            'turn_end_time' => $this->debate->turn_end_time->timestamp ?? 0,
        ]);

        session()->flash('success', 'ターンを進めました。');
    }

    public function render()
    {
        return view('livewire.debate-info', [
            'affirmativeUser' => $this->debate->affirmativeUser,
            'negativeUser' => $this->debate->negativeUser,
            'current_speaker' => $this->current_speaker,
        ]);
    }
}
