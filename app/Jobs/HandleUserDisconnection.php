<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\RoomUser;
use App\Events\UserLeftRoom;
use App\Models\Room;
use App\Models\User;


class HandleUserDisconnection implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $roomId;
    protected $userId;
    /**
     * Create a new job instance.
     */
    public function __construct($roomId, $userId)
    {
        $this->roomId = $roomId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $roomUser = RoomUser::where('room_id', $this->roomId)
            ->where('user_id', $this->userId)
            ->first();

        if ($roomUser && $roomUser->status == RoomUser::STATUS_DISCONNECTED) {
            Room::find($this->roomId)
                ->users()
                ->detach($this->userId);

            // 他のユーザーに通知
            $user = User::find($this->userId);
            $room = Room::find($this->roomId);
            broadcast(new UserLeftRoom($room, $user));

            // ルームの状態を更新
            if ($room->status == Room::STATUS_READY) {
                $room->updateStatus(Room::STATUS_WAITING);
            }
        }
    }
}
