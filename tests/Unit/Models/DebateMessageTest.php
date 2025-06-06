<?php

namespace Tests\Unit\Models;

use App\Models\DebateMessage;
use App\Models\Debate;
use App\Models\User;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesDebates;
use Tests\Traits\CreatesUsers;

class DebateMessageTest extends TestCase
{
    use RefreshDatabase, CreatesDebates, CreatesUsers;

    /**
     * @test
     */
    public function test_fillable_attributes()
    {
        $expectedFillable = [
            'debate_id',
            'user_id',
            'message',
            'turn'
        ];

        $debateMessage = new DebateMessage();
        $this->assertEquals($expectedFillable, $debateMessage->getFillable());
    }

    /**
     * @test
     */
    public function test_uses_traits()
    {
        $debateMessage = new DebateMessage();

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($debateMessage));
        $this->assertContains('Illuminate\Database\Eloquent\SoftDeletes', class_uses($debateMessage));
    }

    /**
     * @test
     */
    public function test_factory_creation()
    {
        $debateMessage = DebateMessage::factory()->create();

        $this->assertInstanceOf(DebateMessage::class, $debateMessage);
        $this->assertDatabaseHas('debate_messages', ['id' => $debateMessage->id]);
    }

    /**
     * @test
     */
    public function test_basic_attributes()
    {
        $debate = Debate::factory()->create();
        $user = User::factory()->create();

        $debateMessage = DebateMessage::factory()->create([
            'debate_id' => $debate->id,
            'user_id' => $user->id,
            'message' => 'This is a test message',
            'turn' => 1,
        ]);

        $this->assertEquals($debate->id, $debateMessage->debate_id);
        $this->assertEquals($user->id, $debateMessage->user_id);
        $this->assertEquals('This is a test message', $debateMessage->message);
        $this->assertEquals(1, $debateMessage->turn);
    }

    /**
     * @test
     */
    public function test_soft_deletes()
    {
        $debateMessage = DebateMessage::factory()->create();
        $messageId = $debateMessage->id;

        $debateMessage->delete();

        // Should be soft deleted
        $this->assertDatabaseHas('debate_messages', [
            'id' => $messageId,
        ]);
        $this->assertNotNull($debateMessage->fresh()->deleted_at);

        // Should not be found in normal queries
        $this->assertNull(DebateMessage::find($messageId));

        // Should be found with trashed
        $this->assertNotNull(DebateMessage::withTrashed()->find($messageId));
    }

    /**
     * Relationship tests
     */

    /**
     * @test
     */
    public function test_debate_relationship()
    {
        $debate = Debate::factory()->create();
        $debateMessage = DebateMessage::factory()->create(['debate_id' => $debate->id]);

        $this->assertInstanceOf(Debate::class, $debateMessage->debate);
        $this->assertEquals($debate->id, $debateMessage->debate->id);
    }

    /**
     * @test
     */
    public function test_user_relationship()
    {
        $user = User::factory()->create();
        $debateMessage = DebateMessage::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $debateMessage->user);
        $this->assertEquals($user->id, $debateMessage->user->id);
    }

    /**
     * @test
     */
    public function test_user_relationship_with_soft_deleted_user()
    {
        $user = User::factory()->create();
        $debateMessage = DebateMessage::factory()->create(['user_id' => $user->id]);

        // Soft delete the user
        $user->delete();

        // Should still be accessible through withTrashed
        $this->assertInstanceOf(User::class, $debateMessage->fresh()->user);
        $this->assertEquals($user->id, $debateMessage->fresh()->user->id);
        $this->assertNotNull($debateMessage->fresh()->user->deleted_at);
    }

    /**
     * @test
     */
    public function test_belongs_to_debate_cascade()
    {
        $debate = Debate::factory()->create();
        $debateMessage = DebateMessage::factory()->create(['debate_id' => $debate->id]);

        $debateId = $debate->id;
        $messageId = $debateMessage->id;

        // Delete debate (soft delete)
        $debate->delete();

        // Message should still exist but debate should be soft deleted
        $this->assertNotNull(DebateMessage::find($messageId));
        $this->assertNotNull(Debate::withTrashed()->find($debateId));
        $this->assertNull(Debate::find($debateId));
    }

    /**
     * Factory state tests
     */

    /**
     * @test
     */
    public function test_factory_for_debate()
    {
        $debate = Debate::factory()->create();
        $debateMessage = DebateMessage::factory()->forDebate($debate)->create();

        $this->assertEquals($debate->id, $debateMessage->debate_id);
    }

    /**
     * @test
     */
    public function test_factory_from_user()
    {
        $user = User::factory()->create();
        $debateMessage = DebateMessage::factory()->fromUser($user)->create();

        $this->assertEquals($user->id, $debateMessage->user_id);
    }

    /**
     * @test
     */
    public function test_factory_affirmative()
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();
        $debate = Debate::factory()->create([
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        $debateMessage = DebateMessage::factory()
            ->forDebate($debate)
            ->affirmative()
            ->create();

        $this->assertEquals($affirmativeUser->id, $debateMessage->user_id);
    }

    /**
     * @test
     */
    public function test_factory_negative()
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();
        $debate = Debate::factory()->create([
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        $debateMessage = DebateMessage::factory()
            ->forDebate($debate)
            ->negative()
            ->create();

        $this->assertEquals($negativeUser->id, $debateMessage->user_id);
    }

    /**
     * @test
     */
    public function test_factory_on_turn()
    {
        $debateMessage = DebateMessage::factory()->onTurn(5)->create();

        $this->assertEquals(5, $debateMessage->turn);
    }

    /**
     * @test
     */
    public function test_factory_long_message()
    {
        $debateMessage = DebateMessage::factory()->long()->create();

        $this->assertGreaterThan(200, strlen($debateMessage->message));
    }

    /**
     * @test
     */
    public function test_factory_short_message()
    {
        $debateMessage = DebateMessage::factory()->short()->create();

        $this->assertLessThan(200, strlen($debateMessage->message));
    }

    /**
     * Integration tests
     */

    /**
     * @test
     */
    public function test_debate_with_messages()
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();
        $debate = Debate::factory()->create([
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        // Create messages for different turns
        $message1 = DebateMessage::factory()->create([
            'debate_id' => $debate->id,
            'user_id' => $affirmativeUser->id,
            'turn' => 1,
            'message' => 'Opening statement from affirmative side',
        ]);

        $message2 = DebateMessage::factory()->create([
            'debate_id' => $debate->id,
            'user_id' => $negativeUser->id,
            'turn' => 2,
            'message' => 'Rebuttal from negative side',
        ]);

        $message3 = DebateMessage::factory()->create([
            'debate_id' => $debate->id,
            'user_id' => $affirmativeUser->id,
            'turn' => 3,
            'message' => 'Counter-rebuttal from affirmative side',
        ]);

        // Test relationships
        $this->assertEquals(3, $debate->messages()->count());
        $this->assertEquals($debate->id, $message1->debate->id);
        $this->assertEquals($affirmativeUser->id, $message1->user->id);
        $this->assertEquals($negativeUser->id, $message2->user->id);

        // Test ordering by creation time
        $messages = $debate->messages()->orderBy('created_at')->get();
        $this->assertEquals($message1->id, $messages[0]->id);
        $this->assertEquals($message2->id, $messages[1]->id);
        $this->assertEquals($message3->id, $messages[2]->id);
    }

    /**
     * @test
     */
    public function test_message_with_special_characters()
    {
        $specialMessage = 'Special chars: 日本語 emoji 😊 symbols @#$%^&*()';
        $debateMessage = DebateMessage::factory()->create([
            'message' => $specialMessage,
        ]);

        $this->assertEquals($specialMessage, $debateMessage->message);
    }

    /**
     * @test
     */
    public function test_message_with_very_long_content()
    {
        $longMessage = str_repeat('This is a very long message content. ', 100);
        $debateMessage = DebateMessage::factory()->create([
            'message' => $longMessage,
        ]);

        $this->assertEquals($longMessage, $debateMessage->message);
    }

    /**
     * @test
     */
    public function test_message_turn_boundaries()
    {
        // Test turn 0 (preparation phase)
        $message1 = DebateMessage::factory()->create(['turn' => 0]);
        $this->assertEquals(0, $message1->turn);

        // Test turn 1 (first turn)
        $message2 = DebateMessage::factory()->create(['turn' => 1]);
        $this->assertEquals(1, $message2->turn);

        // Test high turn number
        $message3 = DebateMessage::factory()->create(['turn' => 99]);
        $this->assertEquals(99, $message3->turn);
    }
}
