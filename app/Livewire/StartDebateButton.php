<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Room;
use Livewire\Attributes\On;


class StartDebateButton extends Component
{
    public Room $room;
    public string $status;
    public bool $isCreator;

    public function mount(Room $room): void
    {
        $this->room = $room;
        $this->status = $this->room->status;
    }

    #[On('echo:rooms.{room.id},UserJoinedRoom')]
    #[On('echo:rooms.{room.id},UserLeftRoom')]
    public function updateStatus(array $data): void
    {
        $this->status = $data['room']['status'];
    }

    public function render()
    {
        return view('livewire.start-debate-button');
    }
}
