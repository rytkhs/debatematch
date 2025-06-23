<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Room;
use App\Models\User;
use Illuminate\Broadcasting\PresenceChannel;

class CreatorLeftRoom implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Room $room, public User $creator)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('room.' . $this->room->id),
        ];
    }

    public function broadcastWith()
    {
        $creatorId = $this->creator ? $this->creator->getAuthIdentifier() : null;
        $creatorName = $this->creator ? $this->creator->name : 'Unknown User';

        return [
            'creator' => [
                'id' => $creatorId,
                'name' => $creatorName
            ],
            'room_id' => $this->room->id,
        ];
    }
}
