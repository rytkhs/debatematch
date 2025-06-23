<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DebateEvaluated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public function __construct(public int $debateId)
    {

    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('debate.' . $this->debateId)
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => 'ディベート評価が完了しました',
            'debateId' => $this->debateId,
            'status' => 'evaluated',
            'timestamp' => now()->toDateTimeString()
        ];
    }
}
