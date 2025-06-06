<?php

namespace Tests\Unit\Models;

use App\Models\Debate;
use App\Models\DebateMessage;
use App\Models\DebateEvaluation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesDebates;
use Tests\Traits\CreatesUsers;
use Tests\Traits\CreatesRooms;

class DebateTest extends TestCase
{
    use RefreshDatabase, CreatesDebates, CreatesUsers, CreatesRooms;

    /**
     * @test
     */
    public function test_fillable_attributes()
    {
        $expectedFillable = [
            'room_id',
            'affirmative_user_id',
            'negative_user_id',
            'current_turn',
            'turn_end_time'
        ];

        $debate = new Debate();
        $this->assertEquals($expectedFillable, $debate->getFillable());
    }

    /**
     * @test
     */
    public function test_casts()
    {
        $expectedCasts = [
            'id' => 'int',
            'turn_end_time' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        $debate = new Debate();
        $this->assertEquals($expectedCasts, $debate->getCasts());
    }

    /**
     * @test
     */
    public function test_factory_creation()
    {
        $debate = Debate::factory()->create();

        $this->assertInstanceOf(Debate::class, $debate);
        $this->assertDatabaseHas('debates', ['id' => $debate->id]);
    }

    /**
     * @test
     */
    public function test_basic_attributes()
    {
        $room = Room::factory()->create();
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
            'current_turn' => 1,
            'turn_end_time' => now()->addMinutes(5),
        ]);

        $this->assertEquals($room->id, $debate->room_id);
        $this->assertEquals($affirmativeUser->id, $debate->affirmative_user_id);
        $this->assertEquals($negativeUser->id, $debate->negative_user_id);
        $this->assertEquals(1, $debate->current_turn);
        $this->assertInstanceOf(\Carbon\Carbon::class, $debate->turn_end_time);
    }

    /**
     * @test
     */
    public function test_turn_end_time_cast()
    {
        $turnEndTime = now()->addMinutes(10);
        $debate = Debate::factory()->create([
            'turn_end_time' => $turnEndTime,
        ]);

        $debate = $debate->fresh();
        $this->assertInstanceOf(\Carbon\Carbon::class, $debate->turn_end_time);
        $this->assertEquals($turnEndTime->format('Y-m-d H:i:s'), $debate->turn_end_time->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function test_soft_deletes()
    {
        $debate = Debate::factory()->create();
        $debateId = $debate->id;

        $debate->delete();

        // Should be soft deleted
        $this->assertDatabaseHas('debates', [
            'id' => $debateId,
        ]);
        $this->assertNotNull($debate->fresh()->deleted_at);

        // Should not be found in normal queries
        $this->assertNull(Debate::find($debateId));

        // Should be found with trashed
        $this->assertNotNull(Debate::withTrashed()->find($debateId));
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
        $debate = Debate::factory()->create(['room_id' => $room->id]);

        $this->assertInstanceOf(Room::class, $debate->room);
        $this->assertEquals($room->id, $debate->room->id);
    }

    /**
     * @test
     */
    public function test_affirmative_user_relationship()
    {
        $user = User::factory()->create();
        $debate = Debate::factory()->create(['affirmative_user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $debate->affirmativeUser);
        $this->assertEquals($user->id, $debate->affirmativeUser->id);
    }

    /**
     * @test
     */
    public function test_negative_user_relationship()
    {
        $user = User::factory()->create();
        $debate = Debate::factory()->create(['negative_user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $debate->negativeUser);
        $this->assertEquals($user->id, $debate->negativeUser->id);
    }

    /**
     * @test
     */
    public function test_affirmative_user_relationship_with_soft_deleted_user()
    {
        $user = User::factory()->create();
        $debate = Debate::factory()->create(['affirmative_user_id' => $user->id]);

        // Soft delete the user
        $user->delete();

        // Should still be accessible through withTrashed
        $this->assertInstanceOf(User::class, $debate->fresh()->affirmativeUser);
        $this->assertEquals($user->id, $debate->fresh()->affirmativeUser->id);
        $this->assertNotNull($debate->fresh()->affirmativeUser->deleted_at);
    }

    /**
     * @test
     */
    public function test_negative_user_relationship_with_soft_deleted_user()
    {
        $user = User::factory()->create();
        $debate = Debate::factory()->create(['negative_user_id' => $user->id]);

        // Soft delete the user
        $user->delete();

        // Should still be accessible through withTrashed
        $this->assertInstanceOf(User::class, $debate->fresh()->negativeUser);
        $this->assertEquals($user->id, $debate->fresh()->negativeUser->id);
        $this->assertNotNull($debate->fresh()->negativeUser->deleted_at);
    }

    /**
     * @test
     */
    public function test_messages_relationship()
    {
        $debate = Debate::factory()->create();
        $message1 = DebateMessage::factory()->create(['debate_id' => $debate->id]);
        $message2 = DebateMessage::factory()->create(['debate_id' => $debate->id]);

        $this->assertEquals(2, $debate->messages()->count());
        $this->assertTrue($debate->messages->contains($message1));
        $this->assertTrue($debate->messages->contains($message2));
    }

    /**
     * @test
     */
    public function test_evaluations_relationship()
    {
        $debate = Debate::factory()->create();
        $evaluation = DebateEvaluation::factory()->create(['debate_id' => $debate->id]);

        $this->assertInstanceOf(DebateEvaluation::class, $debate->evaluations);
        $this->assertEquals($evaluation->id, $debate->evaluations->id);
    }

    /**
     * @test
     */
    public function test_relationship_constraints()
    {
        $room = Room::factory()->create();
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        // Test foreign key constraints exist
        $this->assertEquals($room->id, $debate->room_id);
        $this->assertEquals($affirmativeUser->id, $debate->affirmative_user_id);
        $this->assertEquals($negativeUser->id, $debate->negative_user_id);
    }

    /**
     * @test
     */
    public function test_debate_with_null_users()
    {
        $room = Room::factory()->create();
        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => null,
            'negative_user_id' => null,
        ]);

        $this->assertNull($debate->affirmative_user_id);
        $this->assertNull($debate->negative_user_id);
        $this->assertNull($debate->affirmativeUser);
        $this->assertNull($debate->negativeUser);
    }

    /**
     * Factory state tests
     */

    /**
     * @test
     */
    public function test_factory_for_room()
    {
        $room = Room::factory()->create();
        $debate = Debate::factory()->forRoom($room)->create();

        $this->assertEquals($room->id, $debate->room_id);
    }

    /**
     * @test
     */
    public function test_factory_with_users()
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        $debate = Debate::factory()
            ->withUsers($affirmativeUser, $negativeUser)
            ->create();

        $this->assertEquals($affirmativeUser->id, $debate->affirmative_user_id);
        $this->assertEquals($negativeUser->id, $debate->negative_user_id);
    }

    /**
     * @test
     */
    public function test_factory_with_current_turn()
    {
        $debate = Debate::factory()->withCurrentTurn(3)->create();

        $this->assertEquals(3, $debate->current_turn);
        $this->assertNotNull($debate->turn_end_time);
    }

    /**
     * @test
     */
    public function test_factory_finished()
    {
        $debate = Debate::factory()->finished()->create();

        $this->assertEquals(-1, $debate->current_turn);
        $this->assertNull($debate->turn_end_time);
    }

    /**
     * @test
     */
    public function test_factory_with_messages()
    {
        $debate = Debate::factory()->withMessages(3)->create();

        $this->assertEquals(3, $debate->messages()->count());
    }

    /**
     * @test
     */
    public function test_factory_with_evaluation()
    {
        $debate = Debate::factory()->withEvaluation()->create();

        $this->assertInstanceOf(DebateEvaluation::class, $debate->evaluations);
    }

    /**
     * Integration tests
     */

    /**
     * @test
     */
    public function test_complete_debate_scenario()
    {
        $room = Room::factory()->create();
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
            'current_turn' => 1,
        ]);

        // Add messages
        DebateMessage::factory()->create([
            'debate_id' => $debate->id,
            'user_id' => $affirmativeUser->id,
            'turn' => 1,
        ]);

        DebateMessage::factory()->create([
            'debate_id' => $debate->id,
            'user_id' => $negativeUser->id,
            'turn' => 2,
        ]);

        // Add evaluation
        DebateEvaluation::factory()->create([
            'debate_id' => $debate->id,
            'winner' => DebateEvaluation::WINNER_AFFIRMATIVE,
        ]);

        $debate = $debate->fresh();

        // Test all relationships work together
        $this->assertEquals($room->id, $debate->room->id);
        $this->assertEquals($affirmativeUser->id, $debate->affirmativeUser->id);
        $this->assertEquals($negativeUser->id, $debate->negativeUser->id);
        $this->assertEquals(2, $debate->messages()->count());
        $this->assertEquals(DebateEvaluation::WINNER_AFFIRMATIVE, $debate->evaluations->winner);
    }

    /**
     * Early termination functionality tests
     */

    /**
     * @test
     */
    public function test_can_request_early_termination_affirmative_user()
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();
        $debate = Debate::factory()->create([
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        $this->assertTrue($debate->canRequestEarlyTermination($affirmativeUser->id));
    }

    /**
     * @test
     */
    public function test_can_request_early_termination_negative_user()
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();
        $debate = Debate::factory()->create([
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        $this->assertTrue($debate->canRequestEarlyTermination($negativeUser->id));
    }

    /**
     * @test
     */
    public function test_can_request_early_termination_non_participant()
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();
        $nonParticipant = User::factory()->create();
        $debate = Debate::factory()->create([
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        $this->assertFalse($debate->canRequestEarlyTermination($nonParticipant->id));
    }

    /**
     * @test
     */
    public function test_can_respond_to_early_termination_affirmative_user()
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();
        $debate = Debate::factory()->create([
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        $this->assertTrue($debate->canRespondToEarlyTermination($affirmativeUser->id));
    }

    /**
     * @test
     */
    public function test_can_respond_to_early_termination_negative_user()
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();
        $debate = Debate::factory()->create([
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        $this->assertTrue($debate->canRespondToEarlyTermination($negativeUser->id));
    }

    /**
     * @test
     */
    public function test_can_respond_to_early_termination_non_participant()
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();
        $nonParticipant = User::factory()->create();
        $debate = Debate::factory()->create([
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        $this->assertFalse($debate->canRespondToEarlyTermination($nonParticipant->id));
    }

    /**
     * @test
     */
    public function test_early_termination_with_invalid_user_ids()
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();
        $debate = Debate::factory()->create([
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        // Test with null user ID (nonexistent user)
        $this->assertFalse($debate->canRequestEarlyTermination(99999));
        $this->assertFalse($debate->canRespondToEarlyTermination(99999));

        // Test with zero user ID
        $this->assertFalse($debate->canRequestEarlyTermination(0));
        $this->assertFalse($debate->canRespondToEarlyTermination(0));

        // Test with negative user ID
        $this->assertFalse($debate->canRequestEarlyTermination(-1));
        $this->assertFalse($debate->canRespondToEarlyTermination(-1));
    }

    /**
     * @test
     */
    public function test_early_termination_with_null_debate_users()
    {
        $debate = Debate::factory()->create([
            'affirmative_user_id' => null,
            'negative_user_id' => null,
        ]);

        $user = User::factory()->create();

        $this->assertFalse($debate->canRequestEarlyTermination($user->id));
        $this->assertFalse($debate->canRespondToEarlyTermination($user->id));
    }

    /**
     * @test
     */
    public function test_early_termination_with_partially_null_users()
    {
        $affirmativeUser = User::factory()->create();

        // Debate with only affirmative user
        $debate1 = Debate::factory()->create([
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => null,
        ]);

        $this->assertTrue($debate1->canRequestEarlyTermination($affirmativeUser->id));
        $this->assertTrue($debate1->canRespondToEarlyTermination($affirmativeUser->id));

        // Debate with only negative user
        $negativeUser = User::factory()->create();
        $debate2 = Debate::factory()->create([
            'affirmative_user_id' => null,
            'negative_user_id' => $negativeUser->id,
        ]);

        $this->assertTrue($debate2->canRequestEarlyTermination($negativeUser->id));
        $this->assertTrue($debate2->canRespondToEarlyTermination($negativeUser->id));
    }

    /**
     * @test
     */
    public function test_early_termination_with_soft_deleted_users()
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();
        $debate = Debate::factory()->create([
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        // Soft delete affirmative user
        $affirmativeUser->delete();

        // Should still work with deleted user ID
        $this->assertTrue($debate->canRequestEarlyTermination($affirmativeUser->id));
        $this->assertTrue($debate->canRespondToEarlyTermination($affirmativeUser->id));

        // Negative user should still work normally
        $this->assertTrue($debate->canRequestEarlyTermination($negativeUser->id));
        $this->assertTrue($debate->canRespondToEarlyTermination($negativeUser->id));
    }

    /**
     * @test
     */
    public function test_early_termination_boundary_conditions()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $debate = Debate::factory()->create([
            'affirmative_user_id' => $user1->id,
            'negative_user_id' => $user2->id,
        ]);

        // Test with same user IDs
        $this->assertTrue($debate->canRequestEarlyTermination($user1->id));
        $this->assertTrue($debate->canRequestEarlyTermination($user2->id));

        // Test with different user ID
        $this->assertFalse($debate->canRequestEarlyTermination($user3->id));

        // Test both methods return same result for same inputs
        $this->assertEquals(
            $debate->canRequestEarlyTermination($user1->id),
            $debate->canRespondToEarlyTermination($user1->id)
        );

        $this->assertEquals(
            $debate->canRequestEarlyTermination($user2->id),
            $debate->canRespondToEarlyTermination($user2->id)
        );

        $this->assertEquals(
            $debate->canRequestEarlyTermination($user3->id),
            $debate->canRespondToEarlyTermination($user3->id)
        );
    }

    /**
     * @test
     */
    public function test_early_termination_permissions_consistency()
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();
        $debate = Debate::factory()->create([
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        // Both methods should return identical results for the same user
        $this->assertEquals(
            $debate->canRequestEarlyTermination($affirmativeUser->id),
            $debate->canRespondToEarlyTermination($affirmativeUser->id)
        );

        $this->assertEquals(
            $debate->canRequestEarlyTermination($negativeUser->id),
            $debate->canRespondToEarlyTermination($negativeUser->id)
        );

        // Both participants should have equal permissions
        $this->assertEquals(
            $debate->canRequestEarlyTermination($affirmativeUser->id),
            $debate->canRequestEarlyTermination($negativeUser->id)
        );

        $this->assertEquals(
            $debate->canRespondToEarlyTermination($affirmativeUser->id),
            $debate->canRespondToEarlyTermination($negativeUser->id)
        );
    }
}
