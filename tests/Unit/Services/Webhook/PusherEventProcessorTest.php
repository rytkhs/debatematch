<?php

namespace Tests\Unit\Services\Webhook;

use Tests\TestCase;
use App\Services\Webhook\PusherEventProcessor;
use App\Services\Connection\ConnectionCoordinator;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

class PusherEventProcessorTest extends TestCase
{
    use RefreshDatabase;

    private PusherEventProcessor $eventProcessor;
    private ConnectionCoordinator|MockObject $connectionCoordinator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionCoordinator = $this->createMock(ConnectionCoordinator::class);
        $this->eventProcessor = new PusherEventProcessor($this->connectionCoordinator);

        Log::spy();
    }

    #[Test]
    public function processes_member_removed_event_successfully()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $event = [
            'name' => 'member_removed',
            'channel' => 'presence-room.' . $room->id,
            'user_id' => $user->id
        ];

        $this->connectionCoordinator
            ->expects($this->once())
            ->method('handleDisconnection')
            ->with($user->id, ['type' => 'room', 'id' => $room->id]);

        $this->eventProcessor->processEvent($event);
    }

    #[Test]
    public function processes_member_added_event_successfully()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $event = [
            'name' => 'member_added',
            'channel' => 'presence-room.' . $room->id,
            'user_id' => $user->id
        ];

        $this->connectionCoordinator
            ->expects($this->once())
            ->method('handleReconnection')
            ->with($user->id, ['type' => 'room', 'id' => $room->id]);

        $this->eventProcessor->processEvent($event);
    }

    #[Test]
    public function skips_event_for_nonexistent_user()
    {
        $room = Room::factory()->create();

        $event = [
            'name' => 'member_removed',
            'channel' => 'presence-room.' . $room->id,
            'user_id' => 99999 // 存在しないユーザーID
        ];

        $this->connectionCoordinator
            ->expects($this->never())
            ->method('handleDisconnection');

        $this->eventProcessor->processEvent($event);
    }

    #[Test]
    public function skips_disconnection_for_deleted_room()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create(['status' => Room::STATUS_DELETED]);

        $event = [
            'name' => 'member_removed',
            'channel' => 'presence-room.' . $room->id,
            'user_id' => $user->id
        ];

        $this->connectionCoordinator
            ->expects($this->never())
            ->method('handleDisconnection');

        $this->eventProcessor->processEvent($event);
    }

    #[Test]
    public function skips_disconnection_for_finished_debate()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create(['status' => Room::STATUS_FINISHED]);
        $debate = Debate::factory()->create([
            'room_id' => $room->id
        ]);

        $event = [
            'name' => 'member_removed',
            'channel' => 'presence-debate.' . $debate->id,
            'user_id' => $user->id
        ];

        $this->connectionCoordinator
            ->expects($this->never())
            ->method('handleDisconnection');

        $this->eventProcessor->processEvent($event);
    }

    #[Test]
    public function handles_debate_channel_context()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $debate = Debate::factory()->create(['room_id' => $room->id]);

        $event = [
            'name' => 'member_removed',
            'channel' => 'presence-debate.' . $debate->id,
            'user_id' => $user->id
        ];

        $this->connectionCoordinator
            ->expects($this->once())
            ->method('handleDisconnection')
            ->with($user->id, ['type' => 'debate', 'id' => $debate->id]);

        $this->eventProcessor->processEvent($event);
    }

    #[Test]
    public function ignores_unknown_event_types()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $event = [
            'name' => 'unknown_event',
            'channel' => 'presence-room.' . $room->id,
            'user_id' => $user->id
        ];

        $this->connectionCoordinator
            ->expects($this->never())
            ->method('handleDisconnection');

        $this->connectionCoordinator
            ->expects($this->never())
            ->method('handleReconnection');

        $this->eventProcessor->processEvent($event);
    }

    #[Test]
    public function handles_soft_deleted_users()
    {
        $user = User::factory()->create();
        $user->delete(); // ソフトデリート

        $room = Room::factory()->create();

        $event = [
            'name' => 'member_removed',
            'channel' => 'presence-room.' . $room->id,
            'user_id' => $user->id
        ];

        $this->connectionCoordinator
            ->expects($this->once())
            ->method('handleDisconnection');

        $this->eventProcessor->processEvent($event);
    }
}
