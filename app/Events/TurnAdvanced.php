<?php

namespace App\Events;

use App\Models\Debate;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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
            new Channel('debate.' . $this->debate->room_id),
        ];
    }

    public function broadcastWith()
    {
        return [
            'current_turn' => $this->debate->current_turn,
            'turn_name' => Debate::$turns[$this->debate->current_turn]['name'],
            'next_turn_name' => Debate::$turns[$this->debate->current_turn+1]['name'] ?? '終了',
            'turn_duration' => Debate::$turns[$this->debate->current_turn]['duration'],
            'turn_speaker' => Debate::$turns[$this->debate->current_turn]['speaker'],
            'turn_end_time' => $this->debate->turn_end_time->timestamp,
            'speaker' => Debate::$turns[$this->debate->current_turn]['speaker'],
        ];
    }
}
