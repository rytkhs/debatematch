<?php

namespace App\Livewire;

use App\Models\Room;
use Livewire\Component;
use Livewire\Attributes\On;

class RoomStatus extends Component
{
    public Room $room;
    public bool $isCreator;

    public function mount(Room $room)
    {
        $this->room = $room;
    }


    public function getListeners()
    {
        return [
            // "echo-private:rooms.{$this->room->id},RoomUpdated" => 'refreshRoom',
            "echo:rooms,StatusUpdated" => 'refreshRoom',
        ];
    }

    public function refreshRoom()
    {
        $this->room->refresh();
    }
    public function render()
    {
        return view('livewire.room-status');
    }
}
