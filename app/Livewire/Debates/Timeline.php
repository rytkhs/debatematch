<?php

namespace App\Livewire\Debates;

use Livewire\Component;
use App\Models\Debate;
use Livewire\Attributes\On;
use App\Services\DebateService;

class Timeline extends Component
{
    public Debate $debate;
    public array $format = [];
    public int $currentTurn = 1;
    protected $debateService;

    public function boot(DebateService $debateService)
    {
        $this->debateService = $debateService;
    }

    public function mount(Debate $debate): void
    {
        $this->debate = $debate;
        $this->format = $this->getFilteredTurns();
        $this->currentTurn = $this->debate->current_turn;
    }

    #[On("echo-private:debate.{debate.id},TurnAdvanced")]
    public function handleTurnAdvanced(array $data): void
    {
        if (isset($data['current_turn'])) {
            $this->currentTurn = $data['current_turn'];
        }
    }

    private function getFilteredTurns(): array
    {
        return $this->debateService->getFormat($this->debate);
    }

    public function render()
    {
        return view('livewire.debates.timeline', [
            'format' => $this->format,
            'currentTurn' => $this->currentTurn
        ]);
    }
}
