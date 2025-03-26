<?php

namespace App\Livewire\Rooms;

use Livewire\Component;
use App\Models\Room;
use Livewire\Attributes\On;

class Participants extends Component
{
    public Room $room;
    public $affirmativeDebater;
    public $negativeDebater;

    public function mount(Room $room)
    {
        $this->room = $room;
        $this->affirmativeDebater = $room->users->firstWhere('pivot.side', 'affirmative')->name ?? null;
        $this->negativeDebater = $room->users->firstWhere('pivot.side', 'negative')->name ?? null;
    }


    #[On('echo:rooms.{room.id},UserJoinedRoom')]
    #[On('echo:rooms.{room.id},UserLeftRoom')]
    public function updateParticipants()
    {
        $this->room->load('users');
        $this->affirmativeDebater = $this->room->users->firstWhere('pivot.side', 'affirmative')->name ?? null;
        $this->negativeDebater = $this->room->users->firstWhere('pivot.side', 'negative')->name ?? null;
    }


    public function render()
    {
        return view('livewire.rooms.participants');
    }
}
