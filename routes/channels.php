<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Debate;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('debate.{debateId}', function ($user, $debateId) {
    $debate = Debate::find($debateId);
    if ($debate && ($debate->affirmative_user_id == $user->id || $debate->negative_user_id == $user->id))
    {
        return true;
    }

    return false;

});

Broadcast::channel('debate.{roomId}', function ($user, $roomId) {
    // ユーザーがそのルームに参加しているか確認
    return $user->rooms()->where('room_id', $roomId)->exists();
});

Broadcast::channel('debate.{roomId}', function ($user, $roomId) {
    // ユーザーがそのルームに参加しているか確認
    return $user->rooms()->where('room_id', $roomId)->exists();
});
