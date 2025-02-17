<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Debate;
use App\Models\DebateMessage;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Collection;

/**
 * ディベートのチャットメッセージ一覧を表示・更新するコンポーネント
 */
class DebateChat extends Component
{
    public Debate $debate;
    public string $activeTab = 'all';
    public Collection $filteredMessages;

    public function mount(Debate $debate): void
    {
        $this->debate = $debate;
        $this->filteredMessages = new Collection();
        $this->loadMessages();
    }

    /**
     * メッセージ受信時のハンドラ
     */
    #[On("echo-private:debate.{debate.room_id},DebateMessageSent")]
    public function handleMessageReceived(): void
    {
        $this->loadMessages();
        $this->scrollToBottom();
    }

    /**
     * メッセージの取得とフィルタリング
     */
    private function loadMessages(): void
    {
        $query = $this->debate->messages()
            ->with('user')
            ->orderBy('created_at');

        $this->filteredMessages = $this->filterMessagesByTab($query);
    }

    /**
     * タブに応じたメッセージのフィルタリング
     */
    private function filterMessagesByTab($query)
    {
        return $this->activeTab === 'all'
            ? $query->get()
            : $query->where('turn', $this->activeTab)->get();
    }

    /**
     * スクロールを最下部へ移動
     */
    private function scrollToBottom(): void
    {
        $this->dispatch('scroll-to-bottom');
    }

    /**
     * メッセージ送信後の更新
     */
    #[On("message-sent")]
    public function refreshMessages(): void
    {
        $this->loadMessages();
    }

    /**
     * タブ切り替え時の更新
     */
    #[On("update-active-tab")]
    public function updateActiveTab(string $activeTab): void
    {
        $this->activeTab = $activeTab;
        $this->loadMessages();
    }

    public function render()
    {
        return view('livewire.debate-chat', [
            'messages' => $this->filteredMessages,
            'turns' => $this->debate->getTurns(),
        ]);
    }
}
