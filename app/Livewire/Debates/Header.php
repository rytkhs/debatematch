<?php

namespace App\Livewire\Debates;

use Livewire\Component;
use App\Models\Debate;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Livewire\Attributes\On;
use App\Services\DebateService;
use Illuminate\Support\Facades\Lang;

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
    protected $debateService;

    public function boot(DebateService $debateService)
    {
        $this->debateService = $debateService;
    }

    public function mount(Debate $debate): void
    {
        $this->debate = $debate;
        $this->syncTurnState();
    }

    #[On("echo-private:debate.{debate.id},TurnAdvanced")]
    public function handleTurnAdvanced($data): void
    {
        $this->currentTurn = $data['turn_number'] ?? $this->debate->refresh()->current_turn;

        $format = $this->debateService->getFormat($this->debate);

        $this->currentTurnName = $format[$this->currentTurn]['name'] ?? __('messages.finished');
        $this->nextTurnName = $format[$this->currentTurn + 1]['name'] ?? __('messages.finished');

        $this->currentSpeaker = $data['speaker'] ?? null;
        $this->isPrepTime = $data['is_prep_time'] ?? false;
        $this->turnEndTime = $data['turn_end_time'] ?? null;

        $this->isMyTurn = $this->checkIfUsersTurn(Auth::id());

        $this->debate->current_turn = $this->currentTurn;
        if (isset($data['turn_end_time'])) {
            $this->debate->turn_end_time = Carbon::createFromTimestamp($data['turn_end_time']);
        } else {
            $this->debate->turn_end_time = null;
        }

        $this->dispatch('turn-advanced', [
            'turnEndTime' => $this->turnEndTime
        ]);

        if ($this->isMyTurn) {
            $this->dispatch('showFlashMessage', __('flash.header.turn.my_turn'), 'info');
        }
    }

    private function syncTurnState(): void
    {
        $this->debate->refresh();

        $currentTurn = $this->debate->current_turn;
        $this->currentTurn = $currentTurn;

        $format = $this->debateService->getFormat($this->debate);

        $this->currentTurnName = $format[$currentTurn]['name'] ?? __('messages.finished');
        $this->nextTurnName = $format[$currentTurn + 1]['name'] ?? __('messages.finished');
        $this->currentSpeaker = $format[$currentTurn]['speaker'] ?? null;
        $this->isPrepTime = $format[$currentTurn]['is_prep_time'] ?? false;

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
