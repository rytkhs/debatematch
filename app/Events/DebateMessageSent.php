<?php

namespace App\Events;

use App\Models\DebateMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class DebateMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $debateMessage;
    /**
     * Create a new event instance.
     */
    public function __construct(DebateMessage $debateMessage)
    {
        $this->debateMessage = $debateMessage->load('user');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('debate.' . $this->debateMessage->debate->room_id),
        ];
    }

    public function broadcastWith()
    {
        return [
            'debateMessage' => [
                'id' => $this->debateMessage->id,
                'debate_id' => $this->debateMessage->debate_id,
                'user' => [
                    'id' => $this->debateMessage->user->id,
                    'name' => $this->debateMessage->user->name,
                ],
                'message' => $this->debateMessage->message,
                'turn' => $this->debateMessage->turn,
                'speaking_time' => $this->debateMessage->speaking_time,
                'created_at' => $this->debateMessage->created_at->timestamp,
            ],
        ];
    }
}
