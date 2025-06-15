<?php

namespace Tests\Traits;

use App\Models\Room;
use App\Models\User;

trait CreatesRooms
{
    /**
     * Create a basic room
     */
    protected function createRoom(array $attributes = []): Room
    {
        return Room::factory()->create($attributes);
    }

    /**
     * Create a room with specific status
     */
    protected function createRoomWithStatus(string $status, array $attributes = []): Room
    {
        return Room::factory()->create(array_merge(['status' => $status], $attributes));
    }

    /**
     * Create a waiting room
     */
    protected function createWaitingRoom(array $attributes = []): Room
    {
        return Room::factory()->waiting()->create($attributes);
    }

    /**
     * Create a ready room
     */
    protected function createReadyRoom(array $attributes = []): Room
    {
        return Room::factory()->ready()->create($attributes);
    }

    /**
     * Create a debating room
     */
    protected function createDebatingRoom(array $attributes = []): Room
    {
        return Room::factory()->debating()->create($attributes);
    }

    /**
     * Create a finished room
     */
    protected function createFinishedRoom(array $attributes = []): Room
    {
        return Room::factory()->finished()->create($attributes);
    }

    /**
     * Create an AI debate room
     */
    protected function createAIRoom(array $attributes = []): Room
    {
        return Room::factory()->aiDebate()->create($attributes);
    }

    /**
     * Create a room with users
     */
    protected function createRoomWithUsers(array $attributes = []): Room
    {
        return Room::factory()->withUsers()->create($attributes);
    }

    /**
     * Create a room with debate
     */
    protected function createRoomWithDebate(array $attributes = []): Room
    {
        return Room::factory()->withDebate()->create($attributes);
    }

    /**
     * Create multiple rooms
     */
    protected function createRooms(int $count = 3, array $attributes = []): \Illuminate\Database\Eloquent\Collection
    {
        return Room::factory()->count($count)->create($attributes);
    }

    /**
     * Create room with specific creator
     */
    protected function createRoomWithCreator(User $creator, array $attributes = []): Room
    {
        return Room::factory()->create(array_merge(['created_by' => $creator->id], $attributes));
    }

    /**
     * Create room and add user as participant
     */
    protected function createRoomWithParticipant(User $user, string $side = 'affirmative', array $attributes = []): Room
    {
        $room = $this->createRoom($attributes);
        $room->users()->attach($user->id, ['side' => $side]);
        return $room->fresh();
    }

    /**
     * Create room with both participants
     */
    protected function createRoomWithBothParticipants(User $affirmative, User $negative, array $attributes = []): Room
    {
        $room = $this->createRoom($attributes);
        $room->users()->attach($affirmative->id, ['side' => 'affirmative']);
        $room->users()->attach($negative->id, ['side' => 'negative']);
        return $room->fresh();
    }

    /**
     * Create room in different states for testing
     */
    protected function createRoomsInAllStates(): array
    {
        return [
            'waiting' => $this->createWaitingRoom(),
            'ready' => $this->createReadyRoom(),
            'debating' => $this->createDebatingRoom(),
            'finished' => $this->createFinishedRoom(),
        ];
    }

    /**
     * Create room with custom format
     */
    protected function createRoomWithCustomFormat(array $formatSettings, array $attributes = []): Room
    {
        return Room::factory()->create(array_merge([
            'format_type' => 'custom',
            'custom_format_settings' => $formatSettings,
        ], $attributes));
    }

    /**
     * Create multilingual rooms
     */
    protected function createMultilingualRooms(): array
    {
        return [
            'japanese' => $this->createRoom(['language' => 'ja']),
            'english' => $this->createRoom(['language' => 'en']),
        ];
    }

    /**
     * Assert room has specific status
     */
    protected function assertRoomStatus(Room $room, string $expectedStatus): void
    {
        $this->assertEquals($expectedStatus, $room->status, "Room status should be {$expectedStatus}");
    }

    /**
     * Assert room has participants
     */
    protected function assertRoomHasParticipants(Room $room, int $expectedCount = 2): void
    {
        $this->assertEquals(
            $expectedCount,
            $room->users()->count(),
            "Room should have {$expectedCount} participants"
        );
    }

    /**
     * Assert room has specific participants
     */
    protected function assertRoomHasSpecificParticipants(Room $room, User $affirmative = null, User $negative = null): void
    {
        if ($affirmative) {
            $affirmativeParticipant = $room->users()->wherePivot('side', 'affirmative')->first();
            $this->assertNotNull($affirmativeParticipant, 'Room should have affirmative participant');
            $this->assertEquals($affirmative->id, $affirmativeParticipant->id);
        }

        if ($negative) {
            $negativeParticipant = $room->users()->wherePivot('side', 'negative')->first();
            $this->assertNotNull($negativeParticipant, 'Room should have negative participant');
            $this->assertEquals($negative->id, $negativeParticipant->id);
        }
    }

    /**
     * Assert room creator
     */
    protected function assertRoomCreator(Room $room, User $expectedCreator): void
    {
        $this->assertEquals(
            $expectedCreator->id,
            $room->created_by,
            'Room creator should match expected user'
        );
    }

    /**
     * Assert room can transition to status
     */
    protected function assertRoomCanTransitionTo(Room $room, string $newStatus): void
    {
        $originalStatus = $room->status;
        $room->updateStatus($newStatus);
        $this->assertEquals(
            $newStatus,
            $room->fresh()->status,
            "Room should transition from {$originalStatus} to {$newStatus}"
        );
    }

    /**
     * Assert room format type
     */
    protected function assertRoomFormatType(Room $room, string $expectedType): void
    {
        $this->assertEquals(
            $expectedType,
            $room->format_type,
            "Room format type should be {$expectedType}"
        );
    }

    /**
     * Assert room language
     */
    protected function assertRoomLanguage(Room $room, string $expectedLanguage): void
    {
        $this->assertEquals(
            $expectedLanguage,
            $room->language,
            "Room language should be {$expectedLanguage}"
        );
    }

    /**
     * Assert room has debate
     */
    protected function assertRoomHasDebate(Room $room): void
    {
        $this->assertNotNull($room->debate, 'Room should have an associated debate');
    }

    /**
     * Assert room AI debate setting
     */
    protected function assertRoomIsAIDebate(Room $room, bool $expectedValue = true): void
    {
        $this->assertEquals(
            $expectedValue,
            $room->is_ai_debate,
            "Room AI debate setting should be " . ($expectedValue ? 'true' : 'false')
        );
    }
}
