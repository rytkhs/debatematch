<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RoomUser extends Pivot
{
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
