<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['room_id', 'affirmative_user_id', 'negative_user_id', 'current_turn', 'turn_end_time'];

    protected $casts = ['turn_end_time' => 'datetime'];

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** @return BelongsTo<User, $this> */
    public function affirmativeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affirmative_user_id')->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function negativeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'negative_user_id')->withTrashed();
    }

    /** @return HasMany<DebateMessage, $this> */
    public function debateMessages(): HasMany
    {
        return $this->hasMany(DebateMessage::class);
    }

    /** @return HasOne<DebateEvaluation, $this> */
    public function debateEvaluation(): HasOne
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
