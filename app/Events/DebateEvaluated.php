<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DebateEvaluated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // public string $redirectUrl;

    public function __construct(public int $debateId)
    {
        // $this->redirectUrl = $redirectUrl;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('debate.' . $this->debateId)
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => 'ディベート評価が完了しました',
            'debateId' => $this->debateId,
            // 'redirect_url' => $this->redirectUrl,
            'status' => 'evaluated',
            'timestamp' => now()->toDateTimeString()
        ];
    }
}
