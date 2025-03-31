<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebateMessage extends Model
{
    use HasFactory;

    protected $fillable = ['debate_id', 'user_id', 'message', 'turn'];

    public function debate()
    {
        return $this->belongsTo(Debate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($debateMessage) {
            if ($debateMessage->debate->room_status !== 'debating') {
                return false;
            }
        });
    }
}
