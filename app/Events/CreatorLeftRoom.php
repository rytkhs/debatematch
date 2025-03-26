<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Room;
use App\Models\User;

class CreatorLeftRoom implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $room;
    public $creator;

    /**
     * Create a new event instance.
     */
    public function __construct(Room $room, \Illuminate\Contracts\Auth\Authenticatable $creator)
    {
        $this->room = $room;
        $this->creator = $creator;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('rooms.' . $this->room->id),
        ];
    }

    public function broadcastWith()
    {
        $creatorId = $this->creator->getAuthIdentifier();

        return [
            'creator' => [
                'id' => $creatorId,
                'name' => $this->creator instanceof User ? $this->creator->name : 'Unknown User'
            ],
            'room_id' => $this->room->id,
        ];
    }
}
