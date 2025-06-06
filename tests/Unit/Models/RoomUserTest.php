<?php

namespace Tests\Unit\Models;

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

    /**
     * @test
     */
    public function test_table_name()
    {
        $roomUser = new RoomUser();
        $this->assertEquals('room_users', $roomUser->getTable());
    }

    /**
     * @test
     */
    public function test_side_constants()
    {
        $this->assertEquals('affirmative', RoomUser::SIDE_AFFIRMATIVE);
        $this->assertEquals('negative', RoomUser::SIDE_NEGATIVE);
    }

    /**
     * @test
     */
    public function test_extends_pivot()
    {
        $roomUser = new RoomUser();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\Pivot::class, $roomUser);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

        $this->assertNotNull($directQuery->created_at);
        $this->assertNotNull($directQuery->updated_at);
        $this->assertEquals($timestamp->format('Y-m-d H:i:s'), $directQuery->created_at);
        $this->assertEquals($timestamp->format('Y-m-d H:i:s'), $directQuery->updated_at);
    }

    /**
     * @test
     */
    public function test_detach_user_from_room()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        // Verify attached
        $this->assertEquals(1, $room->users()->count());

        // Detach user
        $room->users()->detach($user->id);

        // Verify detached
        $this->assertEquals(0, $room->users()->count());
        $this->assertNull(RoomUser::where('room_id', $room->id)->where('user_id', $user->id)->first());
    }

    /**
     * @test
     */
    public function test_update_pivot_attributes()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        // Update side
        $room->users()->updateExistingPivot($user->id, ['side' => RoomUser::SIDE_NEGATIVE]);

        $roomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertEquals(RoomUser::SIDE_NEGATIVE, $roomUser->side);
    }

    /**
     * Integration tests
     */

    /**
     * @test
     */
    public function test_room_with_creator_and_participant()
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();
        $room = Room::factory()->create(['created_by' => $creator->id]);

        // Creator joins as affirmative
        $room->users()->attach($creator->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        // Participant joins as negative
        $room->users()->attach($participant->id, ['side' => RoomUser::SIDE_NEGATIVE]);

        $creatorRoomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $creator->id)
            ->first();

        $participantRoomUser = RoomUser::where('room_id', $room->id)
            ->where('user_id', $participant->id)
            ->first();

        // Test creator relationship
        $this->assertTrue($creatorRoomUser->isCreator());
        $this->assertEquals(RoomUser::SIDE_AFFIRMATIVE, $creatorRoomUser->side);
        $this->assertEquals($creator->id, $creatorRoomUser->user->id);
        $this->assertEquals($room->id, $creatorRoomUser->room->id);

        // Test participant relationship
        $this->assertFalse($participantRoomUser->isCreator());
        $this->assertEquals(RoomUser::SIDE_NEGATIVE, $participantRoomUser->side);
        $this->assertEquals($participant->id, $participantRoomUser->user->id);
        $this->assertEquals($room->id, $participantRoomUser->room->id);
    }

    /**
     * @test
     */
    public function test_room_user_cascade_on_room_deletion()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        $roomId = $room->id;
        $userId = $user->id;

        // Verify pivot exists
        $this->assertNotNull(RoomUser::where('room_id', $roomId)->where('user_id', $userId)->first());

        // Delete room (soft delete)
        $room->delete();

        // Pivot should still exist but room should be soft deleted
        $roomUser = RoomUser::where('room_id', $roomId)->where('user_id', $userId)->first();
        $this->assertNotNull($roomUser);

        // Room should be soft deleted
        $this->assertNull(Room::find($roomId));
        $this->assertNotNull(Room::withTrashed()->find($roomId));
    }

    /**
     * @test
     */
    public function test_room_user_cascade_on_user_deletion()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $room->users()->attach($user->id, ['side' => RoomUser::SIDE_AFFIRMATIVE]);

        $roomId = $room->id;
        $userId = $user->id;

        // Verify pivot exists
        $this->assertNotNull(RoomUser::where('room_id', $roomId)->where('user_id', $userId)->first());

        // Delete user (soft delete)
        $user->delete();

        // Pivot should still exist but user should be soft deleted
        $roomUser = RoomUser::where('room_id', $roomId)->where('user_id', $userId)->first();
        $this->assertNotNull($roomUser);

        // User should be soft deleted but accessible through relationship
        $this->assertNotNull($roomUser->user);
        $this->assertNotNull($roomUser->user->deleted_at);
    }

    /**
     * @test
     */
    public function test_room_user_query_by_side()
    {
        $room = Room::factory()->create();
        $affirmativeUser1 = User::factory()->create();
        $affirmativeUser2 = User::factory()->create();
        $negativeUser = User::factory()->create();

        $room->users()->attach([
            $affirmativeUser1->id => ['side' => RoomUser::SIDE_AFFIRMATIVE],
            $affirmativeUser2->id => ['side' => RoomUser::SIDE_AFFIRMATIVE],
            $negativeUser->id => ['side' => RoomUser::SIDE_NEGATIVE],
        ]);

        $affirmativeRoomUsers = RoomUser::where('room_id', $room->id)
            ->where('side', RoomUser::SIDE_AFFIRMATIVE)
            ->get();

        $negativeRoomUsers = RoomUser::where('room_id', $room->id)
            ->where('side', RoomUser::SIDE_NEGATIVE)
            ->get();

        $this->assertEquals(2, $affirmativeRoomUsers->count());
        $this->assertEquals(1, $negativeRoomUsers->count());

        foreach ($affirmativeRoomUsers as $roomUser) {
            $this->assertEquals(RoomUser::SIDE_AFFIRMATIVE, $roomUser->side);
        }

        foreach ($negativeRoomUsers as $roomUser) {
            $this->assertEquals(RoomUser::SIDE_NEGATIVE, $roomUser->side);
        }
    }
}
