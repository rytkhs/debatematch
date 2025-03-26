<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use App\Models\Room;

class ConnectionStatus extends Component
{
    public bool $isOffline = false;
    public bool $isPeerOffline = false;
    public ?string $peerName = null;
    public ?Room $room = null;

    public function mount(?Room $room)
    {
        // Roomモデルをマウント時に受け取る
        $this->room = $room;
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

    #[On('member-offline')]
    public function handlePeerOffline($data): void
    {
        // 相手がオフラインになった場合の処理
        if (!isset($data['id']) || !isset($data['info']['name'])) return;

        $this->isPeerOffline = true;
        $this->peerName = $data['info']['name'] ?? '相手';
    }

    #[On('member-online')]
    public function handlePeerOnline($data): void
    {
        // 相手がオンラインになった場合の処理
        if (!isset($data['id'])) return;

        $this->isPeerOffline = false;
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
        $this->peerName = null;
    }

    public function render()
    {
        // コンポーネントの描画
        return view('livewire.connection-status');
    }
}
