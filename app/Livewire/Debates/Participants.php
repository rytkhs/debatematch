<?php

namespace App\Livewire\Debates;

use Livewire\Component;
use App\Models\Debate;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use App\Services\DebateService;
use Illuminate\Support\Facades\Lang;

class Participants extends Component
{
    public Debate $debate;
    public string $currentTurnName = '';
    public string $nextTurnName = '';
    public ?string $currentSpeaker;
    public bool $isMyTurn = false;
    public array $onlineUsers = [];
    protected $debateService;
    public bool $isProcessing = false;
    public int $aiUserId;

    public function boot(DebateService $debateService)
    {
        $this->debateService = $debateService;
        $this->aiUserId = (int)config('app.ai_user_id', 9);
    }

    public function mount(Debate $debate): void
    {
        $this->debate = $debate;
        $this->syncTurnState();

        $this->onlineUsers[$debate->affirmative_user_id] = $debate->affirmative_user_id !== $this->aiUserId ? false : true; // AI以外は初期オフライン
        $this->onlineUsers[$debate->negative_user_id] = $debate->negative_user_id !== $this->aiUserId ? false : true; // AI以外は初期オフライン
    }

    #[On("echo-private:debate.{debate.id},TurnAdvanced")]
    public function handleTurnAdvanced(): void
    {
        $this->debate->refresh();
        $this->syncTurnState();
        $this->isProcessing = false;
    }

    #[On('member-online')]
    public function handleMemberOnline($data): void
    {
        if (isset($data['id']) && $data['id'] !== $this->aiUserId) { // AIユーザーは無視
            $this->onlineUsers[$data['id']] = true;
        }
    }

    #[On('member-offline')]
    public function handleMemberOffline($data): void
    {
        if (isset($data['id']) && $data['id'] !== $this->aiUserId) { // AIユーザーは無視
            $this->onlineUsers[$data['id']] = false;
        }
    }

    private function syncTurnState(): void
    {
        $turns = $this->debateService->getFormat($this->debate);
        $currentTurn = $this->debate->current_turn;

        $this->currentTurnName = $turns[$currentTurn]['name'] ?? '終了';
        $this->nextTurnName = $turns[$currentTurn + 1]['name'] ?? '終了';
        $this->currentSpeaker = $turns[$currentTurn]['speaker'] ?? null;

        $this->isMyTurn = $this->checkIfUsersTurn(Auth::id());
    }

    private function checkIfUsersTurn(int $userId): bool
    {
        return ($this->currentSpeaker === 'affirmative' && $this->debate->affirmativeUser->id === $userId)
            || ($this->currentSpeaker === 'negative' && $this->debate->negativeUser->id === $userId);
    }

    public function isUserOnline($userId): bool
    {
        if ($userId === $this->aiUserId) {
            return true; // AIは常にオンライン扱い
        }
        return $this->onlineUsers[$userId] ?? false;
    }

    public function advanceTurnManually(): void
    {
        if ($this->isProcessing) {
            return;
        }

        $this->isProcessing = true;

        $currentTurn = $this->debate->current_turn;
        $this->debateService->advanceToNextTurn($this->debate, $currentTurn);

        $this->dispatch('showFlashMessage', __('flash.participants.turn.advanced'), 'success');
    }

    public function render()
    {
        return view('livewire.debates.participants');
    }
}
