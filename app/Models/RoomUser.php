<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RoomUser extends Pivot
{
    protected $table = 'room_users';
    public const ROLE_CREATOR = 'creator';
    public const ROLE_PARTICIPANT = 'participant';

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
