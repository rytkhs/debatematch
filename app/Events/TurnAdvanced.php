<?php

namespace App\Events;

use App\Models\Debate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ターンが進行した際にブロードキャスト
 */
class TurnAdvanced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Debate $debate, public array $additionalData = [])
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
            new PrivateChannel('debate.' . $this->debate->id),
        ];
    }

    public function broadcastWith(): array
    {
        return array_merge([
            'debate_id' => $this->debate->id,
            'current_turn' => $this->debate->current_turn,
            'turn_end_time' => $this->debate->turn_end_time?->timestamp,
        ], $this->additionalData);
    }
}
