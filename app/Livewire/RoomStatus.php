<?php

namespace App\Livewire;

use App\Models\Room;
use Livewire\Component;
use Livewire\Attributes\On;

// class RoomStatus extends Component
// {
//     public Room $room;

//     public function mount(Room $room)
//     {
//         $this->room = $room;
//     }

//     #[On('echo:room.{room.id},RoomJoined')]
//     public function updateRoomStatus()
//     {
//         $this->room->refresh();
//     }

//     public function render()
//     {
//         return view('livewire.room-status');
//     }
// }




class RoomStatus extends Component
{
    public Room $room;
    public bool $isCreator;

    public function mount(Room $room, bool $isCreator)
    {
        $this->room = $room;
        $this->isCreator = $isCreator;
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
