<?php

namespace App\Livewire\Debates;

use Livewire\Component;
use App\Models\Debate;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Livewire\Attributes\On;

class Header extends Component
{
    public Debate $debate;
    public string $currentTurnName = '';
    public string $nextTurnName = '';
    public ?string $currentSpeaker;
    public bool $isMyTurn = false;
    public bool $isPrepTime = false;
    public ?int $turnEndTime;
    public int $currentTurn = 0;

    public function mount(Debate $debate): void
    {
        $this->debate = $debate;
        $this->syncTurnState();
    }

    #[On("echo-private:debate.{debate.id},TurnAdvanced")]
    public function handleTurnAdvanced($data): void
    {
        // データベースリフレッシュの代わりにイベントデータを使用
        $this->currentTurn = $data['current_turn'] ?? $this->debate->current_turn;
        $this->currentTurnName = $data['turn_name'] ?? '終了';
        $this->nextTurnName = $this->getNextTurnName($data['current_turn'] ?? $this->debate->current_turn);
        $this->currentSpeaker = $data['speaker'] ?? null;
        $this->isPrepTime = $data['is_prep_time'] ?? false;
        $this->turnEndTime = $data['turn_end_time'] ?? null;

        $this->isMyTurn = $this->checkIfUsersTurn(Auth::id());

        // 最小限の更新のみ実行しデータベースに負荷をかけない
        $this->debate->current_turn = $this->currentTurn;
        if (isset($data['turn_end_time'])) {
            $this->debate->turn_end_time = Carbon::createFromTimestamp($data['turn_end_time']);
        }

        // フロントエンドにもイベントをディスパッチ
        $this->dispatch('turn-advanced', [
            'turnEndTime' => $this->turnEndTime
        ]);

        // **自分のターンになったらフラッシュメッセージを表示**
        if ($this->isMyTurn) {
            $this->dispatch('showFlashMessage', 'あなたのパートです', 'info');
        }
    }

    private function getNextTurnName($currentTurn): string
    {
        $turns = $this->debate->getFormat();
        $nextTurn = $currentTurn + 1;
        return $turns[$nextTurn]['name'] ?? '終了';
    }

    private function syncTurnState(): void
    {
        $turns = $this->debate->getFormat();
        $currentTurn = $this->debate->current_turn;

        $this->currentTurnName = $turns[$currentTurn]['name'] ?? '終了';
        $this->nextTurnName = $turns[$currentTurn + 1]['name'] ?? '終了';
        $this->currentSpeaker = $turns[$currentTurn]['speaker'] ?? null;
        $this->isPrepTime = $turns[$currentTurn]['is_prep_time'] ?? false;
        $this->turnEndTime = $this->debate->turn_end_time?->timestamp;

        $this->isMyTurn = $this->checkIfUsersTurn(Auth::id());
    }

    private function checkIfUsersTurn(int $userId): bool
    {
        return ($this->currentSpeaker === 'affirmative' && $this->debate->affirmativeUser->id === $userId)
            || ($this->currentSpeaker === 'negative' && $this->debate->negativeUser->id === $userId);
    }

    public function render()
    {
        return view('livewire.debates.header');
    }
}
