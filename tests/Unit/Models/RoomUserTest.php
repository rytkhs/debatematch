<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use App\Models\RoomUser;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\CreatesRooms;
use Tests\Traits\CreatesUsers;

class RoomUserTest extends TestCase
{
    use RefreshDatabase, CreatesRooms, CreatesUsers;

    #[Test]
    public function test_table_name()
    {
        $roomUser = new RoomUser();
        $this->assertEquals('room_users', $roomUser->getTable());
    }

    #[Test]
    public function test_side_constants()
    {
        $this->assertEquals('affirmative', RoomUser::SIDE_AFFIRMATIVE);
        $this->assertEquals('negative', RoomUser::SIDE_NEGATIVE);
    }

    #[Test]
    public function test_extends_pivot()
    {
        $roomUser = new RoomUser();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\Pivot::class, $roomUser);
    }

    #[Test]
    public function test_pivot_creation_with_attributes()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        // Attach user to room with side attribute
        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        // Get the pivot record
        $roomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($roomUser);
        $this->assertEquals($room->id, $roomUser->room_id);
        $this->assertEquals($user->id, $roomUser->user_id);
        $this->assertEquals(RoomUser::SIDE_AFFIRMATIVE, $roomUser->side);
    }

    /**
     * Relationship tests
     */
    #[Test]
    public function test_room_relationship()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        $roomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertInstanceOf(Room::class, $roomUser->room);
        $this->assertEquals($room->id, $roomUser->room->id);
    }

    #[Test]
    public function test_user_relationship()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_NEGATIVE]);

        $roomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertInstanceOf(User::class, $roomUser->user);
        $this->assertEquals($user->id, $roomUser->user->id);
    }

    #[Test]
    public function test_user_relationship_with_soft_deleted_user()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        $roomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        // Soft delete the user
        $user->delete();

        // Should still be accessible through withTrashed
        $this->assertInstanceOf(User::class, $roomUser->fresh()->user);
        $this->assertEquals($user->id, $roomUser->fresh()->user->id);
        $this->assertNotNull($roomUser->fresh()->user->deleted_at);
    }

    /**
     * Method tests
     */
    #[Test]
    public function test_is_creator_method_when_user_is_creator()
    {
        $creator = User::factory()->create();
        $room = Room::factory()->create(['created_by' => $creator->id]);

        $room->users()->attach($creator->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        $roomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $creator->id)
            ->first();

        $this->assertTrue($roomUser->isCreator());
    }

    #[Test]
    public function test_is_creator_method_when_user_is_not_creator()
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();
        $room = Room::factory()->create(['created_by' => $creator->id]);

        $room->users()->attach($participant->id, ['side' => RoomUser::SIDE_NEGATIVE]);

        $roomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $participant->id)
            ->first();

        $this->assertFalse($roomUser->isCreator());
    }

    /**
     * Side functionality tests
     */
    #[Test]
    public function test_affirmative_side_assignment()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        $roomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertEquals(RoomUser::SIDE_AFFIRMATIVE, $roomUser->side);
    }

    #[Test]
    public function test_negative_side_assignment()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_NEGATIVE]);

        $roomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertEquals(RoomUser::SIDE_NEGATIVE, $roomUser->side);
    }

    #[Test]
    public function test_multiple_users_in_room()
    {
        $room = Room::factory()->create();
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        $room->users()->attach([
            $affirmativeUser->id => ['side' => RoomUser::SIDE_AFFIRMATIVE],
            $negativeUser->id => ['side' => RoomUser::SIDE_NEGATIVE],
        ]);

        $affirmativeRoomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $affirmativeUser->id)
            ->first();

        $negativeRoomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $negativeUser->id)
            ->first();

        $this->assertEquals(RoomUser::SIDE_AFFIRMATIVE, $affirmativeRoomUser->side);
        $this->assertEquals(RoomUser::SIDE_NEGATIVE, $negativeRoomUser->side);
        $this->assertEquals($room->id, $affirmativeRoomUser->room_id);
        $this->assertEquals($room->id, $negativeRoomUser->room_id);
    }

    #[Test]
    public function test_room_user_pivot_with_manual_timestamps()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $timestamp = now();

        // Create pivot record with manual timestamps
        $room->users()->attach($user->id, [
            'side' => RoomUser::SIDE_AFFIRMATIVE,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $roomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($roomUser);
        $this->assertEquals($room->id, $roomUser->room_id);
        $this->assertEquals($user->id, $roomUser->user_id);
        $this->assertEquals(RoomUser::SIDE_AFFIRMATIVE, $roomUser->side);

        // Check that the pivot record has the manual timestamps
        $directQuery = DB::table('room_users')
            ->where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($directQuery);
        $this->assertEquals($timestamp->format('Y-m-d H:i:s'), $directQuery->created_at);
        $this->assertEquals($timestamp->format('Y-m-d H:i:s'), $directQuery->updated_at);
    }

    #[Test]
    public function test_detach_user_from_room()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        // Attach user first
        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        $this->assertEquals(1, RoomUser::where('room_id', $room->id)->count());

        // Detach user
        $room->users()->detach($user->id);

        $this->assertEquals(0, RoomUser::where('room_id', $room->id)->count());
    }

    #[Test]
    public function test_update_pivot_attributes()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        // Attach user with affirmative side
        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        // Update to negative side
        $room->users()->updateExistingPivot($user->id, ['side' => RoomUser::SIDE_NEGATIVE]);

        $roomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertEquals(RoomUser::SIDE_NEGATIVE, $roomUser->side);
    }

    /**
     * Integration tests
     */
    #[Test]
    public function test_room_with_creator_and_participant()
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();
        $room = Room::factory()->create(['created_by' => $creator->id]);

        // Attach both users
        $room->users()->attach([
            $creator->id => ['side' => RoomUser::SIDE_AFFIRMATIVE],
            $participant->id => ['side' => RoomUser::SIDE_NEGATIVE],
        ]);

        $creatorRoomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $creator->id)
            ->first();

        $participantRoomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $participant->id)
            ->first();

        // Test creator status
        $this->assertTrue($creatorRoomUser->isCreator());
        $this->assertFalse($participantRoomUser->isCreator());

        // Test sides
        $this->assertEquals(RoomUser::SIDE_AFFIRMATIVE, $creatorRoomUser->side);
        $this->assertEquals(RoomUser::SIDE_NEGATIVE, $participantRoomUser->side);

        // Test relationships
        $this->assertEquals($room->id, $creatorRoomUser->room->id);
        $this->assertEquals($creator->id, $creatorRoomUser->user->id);
        $this->assertEquals($participant->id, $participantRoomUser->user->id);
    }

    #[Test]
    public function test_room_user_cascade_on_room_deletion()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        $roomId = $room->id;
        $userId = $user->id;

        // Verify pivot record exists
        $this->assertEquals(1, RoomUser::where('room_id', $roomId)->count());

        // Soft delete room
        $room->delete();

        // Pivot record should still exist (soft delete doesn't cascade to pivot)
        $this->assertEquals(1, RoomUser::where('room_id', $roomId)->count());

        // But room should be soft deleted
        $this->assertNull(Room::find($roomId));
        $this->assertNotNull(Room::withTrashed()->find($roomId));
    }

    #[Test]
    public function test_room_user_cascade_on_user_deletion()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        $roomId = $room->id;
        $userId = $user->id;

        // Verify pivot record exists
        $this->assertEquals(1, RoomUser::where('user_id', $userId)->count());

        // Soft delete user
        $user->delete();

        // Pivot record should still exist (soft delete doesn't cascade to pivot)
        $this->assertEquals(1, RoomUser::where('user_id', $userId)->count());

        // But user should be soft deleted
        $this->assertNull(User::find($userId));
        $this->assertNotNull(User::withTrashed()->find($userId));
    }

    #[Test]
    public function test_room_user_query_by_side()
    {
        $room = Room::factory()->create();
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        $room->users()->attach([
            $affirmativeUser->id => ['side' => RoomUser::SIDE_AFFIRMATIVE],
            $negativeUser->id => ['side' => RoomUser::SIDE_NEGATIVE],
        ]);

        // Query by affirmative side
        $affirmativeRoomUsers = RoomUser::where('room_id', $room->id)
            ->where('side', RoomUser::SIDE_AFFIRMATIVE)
            ->get();

        // Query by negative side
        $negativeRoomUsers = RoomUser::where('room_id', $room->id)
            ->where('side', RoomUser::SIDE_NEGATIVE)
            ->get();

        $this->assertEquals(1, $affirmativeRoomUsers->count());
        $this->assertEquals(1, $negativeRoomUsers->count());
        $this->assertEquals($affirmativeUser->id, $affirmativeRoomUsers->first()->user_id);
        $this->assertEquals($negativeUser->id, $negativeRoomUsers->first()->user_id);
    }
}
