<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EarlyTerminationExpired implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $debateId;
    public int $requestedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(int $debateId, int $requestedBy)
    {
        $this->debateId = $debateId;
        $this->requestedBy = $requestedBy;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('debate.' . $this->debateId),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'debateId' => $this->debateId,
            'requestedBy' => $this->requestedBy,
        ];
    }
}
