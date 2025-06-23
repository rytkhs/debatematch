<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use App\Events\EarlyTerminationRequested;
use App\Events\EarlyTerminationAgreed;
use App\Events\EarlyTerminationDeclined;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class EarlyTerminationWebSocketTest extends TestCase
{
    use RefreshDatabase;

    private Debate $debate;
    private User $affirmativeUser;
    private User $negativeUser;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用ユーザーを作成
        $this->affirmativeUser = User::factory()->create();
        $this->negativeUser = User::factory()->create();

        // フリーフォーマットのルームを作成
        $room = Room::create([
            'name' => 'Test Room',
            'topic' => 'Test Topic',
            'format_type' => 'free',
            'status' => Room::STATUS_DEBATING,
            'host_user_id' => $this->affirmativeUser->id,
        ]);

        // ディベートを作成
        $this->debate = Debate::create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->affirmativeUser->id,
            'negative_user_id' => $this->negativeUser->id,
            'current_turn' => 1
        ]);
    }

    public function test_early_termination_requested_event_structure()
    {
        $event = new EarlyTerminationRequested($this->debate->id, $this->affirmativeUser->id);

        $this->assertEquals($this->debate->id, $event->debateId);
        $this->assertEquals($this->affirmativeUser->id, $event->requestedBy);
    }

    public function test_early_termination_requested_broadcast_data()
    {
        $event = new EarlyTerminationRequested($this->debate->id, $this->affirmativeUser->id);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('debateId', $broadcastData);
        $this->assertArrayHasKey('requestedBy', $broadcastData);
        $this->assertEquals($this->debate->id, $broadcastData['debateId']);
        $this->assertEquals($this->affirmativeUser->id, $broadcastData['requestedBy']);
    }

    public function test_early_termination_requested_broadcast_channel()
    {
        $event = new EarlyTerminationRequested($this->debate->id, $this->affirmativeUser->id);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('presence-debate.' . $this->debate->id, $channels[0]->name);
    }

    public function test_early_termination_agreed_event_structure()
    {
        $event = new EarlyTerminationAgreed($this->debate->id);

        $this->assertEquals($this->debate->id, $event->debateId);
    }

    public function test_early_termination_agreed_broadcast_data()
    {
        $event = new EarlyTerminationAgreed($this->debate->id);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('debateId', $broadcastData);
        $this->assertEquals($this->debate->id, $broadcastData['debateId']);
    }

    public function test_early_termination_agreed_broadcast_channel()
    {
        $event = new EarlyTerminationAgreed($this->debate->id);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('presence-debate.' . $this->debate->id, $channels[0]->name);
    }

    public function test_early_termination_declined_event_structure()
    {
        $event = new EarlyTerminationDeclined($this->debate->id);

        $this->assertEquals($this->debate->id, $event->debateId);
    }

    public function test_early_termination_declined_broadcast_data()
    {
        $event = new EarlyTerminationDeclined($this->debate->id);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('debateId', $broadcastData);
        $this->assertEquals($this->debate->id, $broadcastData['debateId']);
    }

    public function test_early_termination_declined_broadcast_channel()
    {
        $event = new EarlyTerminationDeclined($this->debate->id);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('presence-debate.' . $this->debate->id, $channels[0]->name);
    }

    public function test_events_implement_should_broadcast()
    {
        $this->assertInstanceOf(
            \Illuminate\Contracts\Broadcasting\ShouldBroadcast::class,
            new EarlyTerminationRequested($this->debate->id, $this->affirmativeUser->id)
        );

        $this->assertInstanceOf(
            \Illuminate\Contracts\Broadcasting\ShouldBroadcast::class,
            new EarlyTerminationAgreed($this->debate->id)
        );

        $this->assertInstanceOf(
            \Illuminate\Contracts\Broadcasting\ShouldBroadcast::class,
            new EarlyTerminationDeclined($this->debate->id)
        );
    }
}
