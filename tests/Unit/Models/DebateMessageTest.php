<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
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

    #[Test]
    public function test_uses_traits()
    {
        $debateMessage = new DebateMessage();

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($debateMessage));
        $this->assertContains('Illuminate\Database\Eloquent\SoftDeletes', class_uses($debateMessage));
    }

    #[Test]
    public function test_factory_creation()
    {
        $debateMessage = DebateMessage::factory()->create();

        $this->assertInstanceOf(DebateMessage::class, $debateMessage);
        $this->assertDatabaseHas('debate_messages', ['id' => $debateMessage->id]);
    }

    #[Test]
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

    #[Test]
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
    #[Test]
    public function test_debate_relationship()
    {
        $debate = Debate::factory()->create();
        $debateMessage = DebateMessage::factory()->create(['debate_id' => $debate->id]);

        $this->assertInstanceOf(Debate::class, $debateMessage->debate);
        $this->assertEquals($debate->id, $debateMessage->debate->id);
    }

    #[Test]
    public function test_user_relationship()
    {
        $user = User::factory()->create();
        $debateMessage = DebateMessage::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $debateMessage->user);
        $this->assertEquals($user->id, $debateMessage->user->id);
    }

    #[Test]
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

    #[Test]
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
    #[Test]
    public function test_factory_for_debate()
    {
        $debate = Debate::factory()->create();
        $debateMessage = DebateMessage::factory()->forDebate($debate)->create();

        $this->assertEquals($debate->id, $debateMessage->debate_id);
    }

    #[Test]
    public function test_factory_from_user()
    {
        $user = User::factory()->create();
        $debateMessage = DebateMessage::factory()->fromUser($user)->create();

        $this->assertEquals($user->id, $debateMessage->user_id);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function test_factory_on_turn()
    {
        $debateMessage = DebateMessage::factory()->onTurn(5)->create();

        $this->assertEquals(5, $debateMessage->turn);
    }

    #[Test]
    public function test_factory_long_message()
    {
        $debateMessage = DebateMessage::factory()->long()->create();

        $this->assertGreaterThan(200, strlen($debateMessage->message));
    }

    #[Test]
    public function test_factory_short_message()
    {
        $debateMessage = DebateMessage::factory()->short()->create();

        $this->assertLessThan(200, strlen($debateMessage->message));
    }

    /**
     * Integration tests
     */
    #[Test]
    public function test_multiple_messages_in_debate()
    {
        $debate = Debate::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $message1 = DebateMessage::factory()->create([
            'debate_id' => $debate->id,
            'user_id' => $user1->id,
            'turn' => 1,
        ]);

        $message2 = DebateMessage::factory()->create([
            'debate_id' => $debate->id,
            'user_id' => $user2->id,
            'turn' => 2,
        ]);

        $this->assertEquals(2, DebateMessage::where('debate_id', $debate->id)->count());
        $this->assertEquals(1, $message1->turn);
        $this->assertEquals(2, $message2->turn);
    }

    #[Test]
    public function test_message_with_special_characters()
    {
        $specialMessage = 'Special chars: !@#$%^&*()_+-=[]{}|;:,.<>?';
        $debateMessage = DebateMessage::factory()->create([
            'message' => $specialMessage,
        ]);

        $this->assertEquals($specialMessage, $debateMessage->message);
    }

    #[Test]
    public function test_message_with_very_long_content()
    {
        $longMessage = str_repeat('This is a very long message. ', 100);
        $debateMessage = DebateMessage::factory()->create([
            'message' => $longMessage,
        ]);

        $this->assertEquals($longMessage, $debateMessage->message);
        $this->assertGreaterThan(1000, strlen($debateMessage->message));
    }

    #[Test]
    public function test_message_turn_boundaries()
    {
        $debateMessage = DebateMessage::factory()->create(['turn' => 1]);
        $this->assertEquals(1, $debateMessage->turn);

        $debateMessage = DebateMessage::factory()->create(['turn' => 255]);
        $this->assertEquals(255, $debateMessage->turn);
    }
}
