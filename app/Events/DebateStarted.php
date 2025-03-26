<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DebateStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $debateId;
    public $roomId;
    /**
     * Create a new event instance.
     */
    public function __construct($debateId, $roomId)
    {
        $this->debateId = $debateId;
        $this->roomId = $roomId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return [
            new Channel('rooms.' . $this->roomId),
        ];
    }

    public function broadcastWith()
    {
        return [
            'debateId' => $this->debateId,
        ];
    }
}
