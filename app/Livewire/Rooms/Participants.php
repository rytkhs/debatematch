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
        $room->load('users');
        $affirmativeUser = $room->users->firstWhere('pivot.side', 'affirmative');
        $negativeUser = $room->users->firstWhere('pivot.side', 'negative');

        $this->affirmativeDebater = $affirmativeUser ? $affirmativeUser->name : null;
        $this->negativeDebater = $negativeUser ? $negativeUser->name : null;
    }


    #[On('echo:private-rooms.{room.id},UserJoinedRoom')]
    #[On('echo:private-rooms.{room.id},UserLeftRoom')]
    public function updateParticipants()
    {
        $this->room->load('users');
        $affirmativeUser = $this->room->users->firstWhere('pivot.side', 'affirmative');
        $negativeUser = $this->room->users->firstWhere('pivot.side', 'negative');

        $this->affirmativeDebater = $affirmativeUser ? $affirmativeUser->name : null;
        $this->negativeDebater = $negativeUser ? $negativeUser->name : null;
    }


    public function render()
    {
        return view('livewire.rooms.participants');
    }
}
