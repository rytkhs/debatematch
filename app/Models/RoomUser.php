<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property-read Room|null $room
 * @property-read User|null $user
 */
class RoomUser extends Pivot
{
    protected $table = 'room_users';

    // サイドの定数
    public const SIDE_AFFIRMATIVE = 'affirmative';
    public const SIDE_NEGATIVE = 'negative';

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * ユーザーがルーム作成者かどうかを判定
     * roomリレーションが事前にロードされていることを前提とする
     */
    public function isCreator(): bool
    {
        // roomリレーションがロードされていない場合は事前にロード
        if (!$this->relationLoaded('room')) {
            $this->load('room');
        }
        return $this->room?->created_by === $this->user_id;
    }
}
