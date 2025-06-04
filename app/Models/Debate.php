<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\DebateService;
use Illuminate\Support\Facades\App;

class Debate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['room_id', 'affirmative_user_id', 'negative_user_id', 'current_turn', 'turn_end_time'];

    protected $casts = ['turn_end_time' => 'datetime'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function affirmativeUser()
    {
        return $this->belongsTo(User::class, 'affirmative_user_id')->withTrashed();
    }

    public function negativeUser()
    {
        return $this->belongsTo(User::class, 'negative_user_id')->withTrashed();
    }

    public function messages()
    {
        return $this->hasMany(DebateMessage::class);
    }

    public function evaluations()
    {
        return $this->hasOne(DebateEvaluation::class);
    }

    /**
     * 指定されたユーザーが早期終了を提案できるかチェック
     */
    public function canRequestEarlyTermination(int $userId): bool
    {
        return $userId === $this->affirmative_user_id || $userId === $this->negative_user_id;
    }

    /**
     * 指定されたユーザーが早期終了提案に応答できるかチェック
     */
    public function canRespondToEarlyTermination(int $userId): bool
    {
        return $userId === $this->affirmative_user_id || $userId === $this->negative_user_id;
    }
}
