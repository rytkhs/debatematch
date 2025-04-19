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
    public bool $isAITurn = false;
    public ?int $turnEndTime;
    public int $currentTurn = 0;
    protected $debateService;
    protected int $aiUserId;

    public function boot(DebateService $debateService)
    {
        $this->debateService = $debateService;
        $this->aiUserId = (int)config('app.ai_user_id', 9);
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

        $this->checkIfUsersTurn(Auth::id());

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

        $this->checkIfUsersTurn(Auth::id());
    }

    /**
     * 現在のターンが誰のものか判定し、isMyTurn と isAITurn を設定する
     */
    private function checkIfUsersTurn(int $userId): void
    {
        $this->isMyTurn = false;
        $this->isAITurn = false;

        if ($this->currentSpeaker === 'affirmative') {
            if ($this->debate->affirmative_user_id === $userId) {
                $this->isMyTurn = true;
            } elseif ($this->debate->room->is_ai_debate && $this->debate->affirmative_user_id === $this->aiUserId) {
                $this->isAITurn = true;
            }
        } elseif ($this->currentSpeaker === 'negative') {
            if ($this->debate->negative_user_id === $userId) {
                $this->isMyTurn = true;
            } elseif ($this->debate->room->is_ai_debate && $this->debate->negative_user_id === $this->aiUserId) {
                $this->isAITurn = true;
            }
        }
    }

    public function render()
    {
        return view('livewire.debates.header');
    }
}
