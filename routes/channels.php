<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Debate;
use App\Models\Room;
use App\Models\User;

// Presence Channel for Room
Broadcast::channel('room.{room}', function (User $user, Room $room) {
    if ($user->rooms()->where('room_id', $room->id)->exists()) {
        return ['id' => $user->id, 'name' => $user->name];
    }

    return false;
});

// Presence Channel for Debate
Broadcast::channel('debate.{debate}', function (User $user, Debate $debate) {
    if ($user->rooms()->where('room_id', $debate->room_id)->exists()) {
        return ['id' => $user->id, 'name' => $user->name];
    }

    return false;
});
