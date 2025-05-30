<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebateMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['debate_id', 'user_id', 'message', 'turn'];

    public function debate()
    {
        return $this->belongsTo(Debate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
