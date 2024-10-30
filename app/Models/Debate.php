<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debate extends Model
{
    use HasFactory;

    protected $fillable = ['room_id', 'affirmative_user_id', 'negative_user_id', 'winner'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function affirmativeUser()
    {
        return $this->belongsTo(User::class, 'affirmative_user_id');
    }

    public function negativeUser()
    {
        return $this->belongsTo(User::class, 'negative_user_id');
    }

    public function messages()
    {
        return $this->hasMany(DebateMessage::class);
    }

    // public function evaluations()
    // {
    //     return $this->hasMany(DebateEvaluation::class);
    // }
}
