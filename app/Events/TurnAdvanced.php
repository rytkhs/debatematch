<?php

namespace App\Events;

use App\Models\Debate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ターンが進行した際にブロードキャスト
 */
class TurnAdvanced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $debate;
    /**
     * Create a new event instance.
     */
    public function __construct(Debate $debate)
    {
        $this->debate = $debate;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('debate.' . $this->debate->room_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'debate_id' => $this->debate->id,
        ];
    }
}
