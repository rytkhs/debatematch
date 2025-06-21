<?php

namespace Tests\Unit\Services\Room;

use Tests\TestCase;
use App\Services\Room\RoomParticipationService;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;

class RoomParticipationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoomParticipationService $participationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->participationService = app(RoomParticipationService::class);
        Event::fake();
    }

    #[Test]
    public function can_join_room_successfully()
    {
        [$creator, $participant, $room] = $this->createRoomWithCreator();

        $result = $this->participationService->joinRoom($room, $participant, 'negative');

        $this->assertEquals(Room::STATUS_READY, $result->status);
        $this->assertTrue($result->users->contains($participant));
        $this->assertEquals('negative', $result->users->where('id', $participant->id)->first()->pivot->side);
    }

    #[Test]
    public function leave_room_as_participant()
    {
        [$creator, $participant, $room] = $this->createRoomWithParticipants();

        $result = $this->participationService->leaveRoom($room, $participant);

        $this->assertEquals(Room::STATUS_WAITING, $result->status);
        $this->assertFalse($result->users->contains($participant));
        $this->assertTrue($result->users->contains($creator));
    }

    #[Test]
    public function leave_room_as_creator_deletes_room()
    {
        [$creator, $participant, $room] = $this->createRoomWithParticipants();

        $result = $this->participationService->leaveRoom($room, $creator);

        $this->assertEquals(Room::STATUS_DELETED, $result->status);
        $this->assertFalse($result->users->contains($creator));
    }

    #[Test]
    public function can_join_room_validation_passes()
    {
        [$creator, $participant, $room] = $this->createRoomWithCreator();

        $validation = $this->participationService->canJoinRoom($room, $participant);

        $this->assertTrue($validation['can_join']);
    }

    #[Test]
    public function cannot_join_full_room()
    {
        [$creator, $participant, $room] = $this->createRoomWithParticipants();
        $thirdUser = User::factory()->create();

        $validation = $this->participationService->canJoinRoom($room, $thirdUser);

        $this->assertFalse($validation['can_join']);
        $this->assertEquals('flash.room.join.full', $validation['error_key']);
    }

    #[Test]
    public function cannot_join_room_already_joined()
    {
        [$creator, $participant, $room] = $this->createRoomWithCreator();

        $validation = $this->participationService->canJoinRoom($room, $creator);

        $this->assertFalse($validation['can_join']);
        $this->assertEquals('flash.room.join.already_joined', $validation['error_key']);
    }

    #[Test]
    public function cannot_join_room_not_waiting()
    {
        [$creator, $participant, $room] = $this->createRoomWithCreator();
        // WAITINGからDEBATINGへは直接遷移できないので、READYを経由する
        $room->updateStatus(Room::STATUS_READY);
        $room->updateStatus(Room::STATUS_DEBATING);

        $validation = $this->participationService->canJoinRoom($room, $participant);

        $this->assertFalse($validation['can_join']);
        $this->assertEquals('flash.room.join.not_available', $validation['error_key']);
    }

    #[Test]
    public function join_room_throws_exception_for_taken_side()
    {
        [$creator, $participant, $room] = $this->createRoomWithCreator();

        $this->expectException(\Exception::class);
        $this->participationService->joinRoom($room, $participant, 'affirmative'); // 作成者と同じ側
    }

    /**
     * 作成者のみが参加したルームを作成
     */
    private function createRoomWithCreator(): array
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();

        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_WAITING,
        ]);

        $room->users()->attach($creator->id, ['side' => 'affirmative']);
        $room->load('users'); // リレーションを再読み込み

        return [$creator, $participant, $room];
    }

    /**
     * 作成者と参加者が両方参加したルームを作成
     */
    private function createRoomWithParticipants(): array
    {
        [$creator, $participant, $room] = $this->createRoomWithCreator();

        $room->users()->attach($participant->id, ['side' => 'negative']);
        $room->updateStatus(Room::STATUS_READY);
        $room->load('users'); // リレーションを再読み込み

        return [$creator, $participant, $room];
    }
}
