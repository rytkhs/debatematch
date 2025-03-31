<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use App\Models\Room;
use App\Models\Debate;
use Illuminate\Support\Facades\Auth;

class ConnectionStatus extends Component
{
    public bool $isOffline = false;
    public bool $isPeerOffline = false;
    public array $onlineUsers = [];
    public ?Debate $debate = null;
    public ?Room $room = null;

    public function mount(?Room $room = null): void
    {
        $this->room = $room;
        $this->debate = $room->debate;
    }

    #[On('connection-lost')]
    public function handleConnectionLost(): void
    {
        // 接続が失われた場合の処理
        $this->isOffline = true;
    }

    #[On('connection-restored')]
    public function handleConnectionRestored(): void
    {
        // 接続が復元した場合の処理
        $this->isOffline = false;
    }

    #[On('member-online')]
    public function handleMemberOnline($data): void
    {
        // 相手がオンラインになった場合の処理
        if (!isset($data['id'])) return;

        $userId = $data['id'];
        $this->onlineUsers[$userId] = true;

        // 相手のオンライン状態を更新
        $this->updatePeerStatus();
    }

    #[On('member-offline')]
    public function handleMemberOffline($data): void
    {
        // 相手がオフラインになった場合の処理
        if (!isset($data['id'])) return;

        $userId = $data['id'];
        $this->onlineUsers[$userId] = false;

        // 相手のオンライン状態を更新
        $this->updatePeerStatus();
    }

    private function updatePeerStatus(): void
    {
        $this->isPeerOffline = false;

        if ($this->debate) {
            // ディベート相手の状態を確認
            $currentUserId = Auth::id();
            $peerId = null;

            if ($this->debate->affirmative_user_id == $currentUserId) {
                $peerId = $this->debate->negative_user_id;
            } elseif ($this->debate->negative_user_id == $currentUserId) {
                $peerId = $this->debate->affirmative_user_id;
            }

            if ($peerId && isset($this->onlineUsers[$peerId])) {
                $this->isPeerOffline = !$this->onlineUsers[$peerId];
            }
        }
    }

    #[On('echo:rooms.{room.id},UserLeftRoom')]
    public function resetState(): void
    {
        // ユーザーが退出した場合、状態を初期化
        if ($this->room === null) {
            return;
        }

        $this->isOffline = false;
        $this->isPeerOffline = false;
        $this->onlineUsers = [];
    }

    public function isUserOnline($userId): bool
    {
        return $this->onlineUsers[$userId] ?? false;
    }

    public function render()
    {
        return view('livewire.connection-status');
    }
}
