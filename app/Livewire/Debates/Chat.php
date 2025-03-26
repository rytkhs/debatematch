<?php

namespace App\Livewire\Debates;

use Livewire\Component;
use App\Models\Debate;
use App\Models\DebateMessage;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Collection;

class Chat extends Component
{
    public Debate $debate;
    public string $activeTab = 'all';
    public Collection $filteredMessages;
    public ?int $previousTurn = null;

    public function mount(Debate $debate): void
    {
        $this->debate = $debate;
        $this->filteredMessages = new Collection();
        $this->loadMessages();
    }

    #[On("echo-private:debate.{debate.id},DebateMessageSent")]
    public function handleMessageReceived(): void
    {
        $this->loadMessages();
        // $this->dispatch('scroll-to-bottom');
        $this->dispatch('message-received');

        // フラッシュメッセージ表示
        $this->dispatch('showFlashMessage', 'メッセージを受信しました', 'info');
    }

    #[On("message-sent")]
    public function refreshMessages(): void
    {
        $this->loadMessages();
    }

    public function updatedActiveTab(string $value): void
    {
        $this->activeTab = $value;
        $this->loadMessages();
    }

    private function loadMessages(): void
    {
        $query = $this->debate->messages()
            ->with('user')
            ->orderBy('created_at');

        $this->filteredMessages = $this->filterMessagesByTab($query);
    }

    private function filterMessagesByTab($query)
    {
        return $this->activeTab === 'all'
            ? $query->get()
            : $query->where('turn', $this->activeTab)->get();
    }

    public function getFilteredTurnsProperty()
    {
        $turns = $this->debate->getFormat();
        // 準備時間のターンを除外
        return array_filter($turns, fn($turn) => !($turn['is_prep_time'] ?? false));
    }

    public function render()
    {
        return view('livewire.debates.chat', [
            'filteredMessages' => $this->filteredMessages,
            'turns' => $this->debate->getFormat(),
            'filteredTurns' => $this->filteredTurns,
            'previousTurn' => $this->previousTurn,
        ]);
    }
}
