<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'topic', 'status', 'created_by'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'room_users')->withPivot('side');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function debates()
    {
        return $this->hasMany(Debate::class);
    }
}
