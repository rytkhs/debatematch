<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Debate;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('debate.{roomId}', function ($user, $roomId) {
    // ユーザーがそのルームに参加しているか確認
    return $user->rooms()->where('room_id', $roomId)->exists();
});

Broadcast::channel('rooms.{roomId}', function ($user, $roomId) {
    if ($user->rooms()->where('room_id', $roomId)->exists()) {
        $side = $user->rooms()->where('room_id', $roomId)->first()->pivot->side;
        return ['id' => $user->id, 'name' => $user->name, 'side' => $side];
    }
    return false;
});

Broadcast::channel('presence-room.{roomId}', function ($user, $roomId) {
    return ['id' => $user->id, 'name' => $user->name];
    });
