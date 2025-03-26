<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Debate;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('debate.{debateId}', function ($user, $debateId) {
    $debate = Debate::find($debateId);
    return $debate && $user->rooms()->where('room_id', $debate->room_id)->exists();
});

Broadcast::channel('rooms.{roomId}', function ($user, $roomId) {
    return $user->rooms()->where('room_id', $roomId)->exists();
});

Broadcast::channel('presence-room.{roomId}', function ($user, $roomId) {
    return ['id' => $user->id, 'name' => $user->name];
});


Broadcast::channel('presence-debate.{debateId}', function ($user, $debateId) {
    return ['id' => $user->id, 'name' => $user->name];
});
