<?php

namespace Tests\Traits;

use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use App\Models\DebateMessage;
use App\Models\DebateEvaluation;

trait CreatesDebates
{
    /**
     * Create a basic debate
     */
    protected function createDebate(array $attributes = []): Debate
    {
        return Debate::factory()->create($attributes);
    }

    /**
     * Create a debate with specific users
     */
    protected function createDebateWithUsers(User $affirmative, User $negative, array $attributes = []): Debate
    {
        return Debate::factory()->create(array_merge([
            'affirmative_user_id' => $affirmative->id,
            'negative_user_id' => $negative->id,
        ], $attributes));
    }

    /**
     * Create a debate with room
     */
    protected function createDebateWithRoom(Room $room, array $attributes = []): Debate
    {
        return Debate::factory()->create(array_merge([
            'room_id' => $room->id,
        ], $attributes));
    }

    /**
     * Create a complete debate setup (room + users + debate)
     */
    protected function createCompleteDebate(array $attributes = []): array
    {
        $affirmative = User::factory()->create();
        $negative = User::factory()->create();
        $room = Room::factory()->create(['created_by' => $affirmative->id]);

        // Attach users to room
        $room->users()->attach($affirmative->id, ['side' => 'affirmative']);
        $room->users()->attach($negative->id, ['side' => 'negative']);

        $debate = Debate::factory()->create(array_merge([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmative->id,
            'negative_user_id' => $negative->id,
        ], $attributes));

        return [
            'debate' => $debate,
            'room' => $room,
            'affirmative' => $affirmative,
            'negative' => $negative,
        ];
    }

    /**
     * Create debate with messages
     */
    protected function createDebateWithMessages(int $messageCount = 5, array $attributes = []): Debate
    {
        return Debate::factory()->withMessages($messageCount)->create($attributes);
    }

    /**
     * Create debate with evaluation
     */
    protected function createDebateWithEvaluation(array $evaluationAttributes = [], array $attributes = []): Debate
    {
        return Debate::factory()->withEvaluation($evaluationAttributes)->create($attributes);
    }

    /**
     * Create multiple debates
     */
    protected function createDebates(int $count = 3, array $attributes = []): \Illuminate\Database\Eloquent\Collection
    {
        return Debate::factory()->count($count)->create($attributes);
    }

    /**
     * Create debate with specific turn
     */
    protected function createDebateWithTurn(int $turn, array $attributes = []): Debate
    {
        return Debate::factory()->create(array_merge([
            'current_turn' => $turn,
        ], $attributes));
    }

    /**
     * Create debate with turn end time
     */
    protected function createDebateWithTurnEndTime(\DateTime $endTime, array $attributes = []): Debate
    {
        return Debate::factory()->create(array_merge([
            'turn_end_time' => $endTime,
        ], $attributes));
    }

    /**
     * Create AI debate
     */
    protected function createAIDebate(User $user, array $attributes = []): array
    {
        $aiUserId = config('app.ai_user_id', 1);
        $aiUser = User::factory()->create(['id' => $aiUserId]);

        $room = Room::factory()->aiDebate()->create(['created_by' => $user->id]);
        $room->users()->attach($user->id, ['side' => 'affirmative']);
        $room->users()->attach($aiUser->id, ['side' => 'negative']);

        $debate = Debate::factory()->create(array_merge([
            'room_id' => $room->id,
            'affirmative_user_id' => $user->id,
            'negative_user_id' => $aiUser->id,
        ], $attributes));

        return [
            'debate' => $debate,
            'room' => $room,
            'user' => $user,
            'ai_user' => $aiUser,
        ];
    }

    /**
     * Create finished debate with evaluation
     */
    protected function createFinishedDebate(string $winner = 'affirmative', array $attributes = []): array
    {
        $setup = $this->createCompleteDebate($attributes);

        $setup['room']->updateStatus(Room::STATUS_FINISHED);

        $evaluation = DebateEvaluation::factory()->create([
            'debate_id' => $setup['debate']->id,
            'winner' => $winner,
            'is_analyzable' => true,
        ]);

        $setup['evaluation'] = $evaluation;
        return $setup;
    }

    /**
     * Add message to debate
     */
    protected function addMessageToDebate(Debate $debate, User $user, string $message, int $turn = null): DebateMessage
    {
        return DebateMessage::factory()->create([
            'debate_id' => $debate->id,
            'user_id' => $user->id,
            'message' => $message,
            'turn' => $turn ?? $debate->current_turn,
        ]);
    }

    /**
     * Add multiple messages to debate
     */
    protected function addMessagesToDebate(Debate $debate, array $messages): \Illuminate\Database\Eloquent\Collection
    {
        $createdMessages = collect();

        foreach ($messages as $messageData) {
            $message = $this->addMessageToDebate(
                $debate,
                $messageData['user'],
                $messageData['message'],
                $messageData['turn'] ?? null
            );
            $createdMessages->push($message);
        }

        return $createdMessages;
    }

    /**
     * Assert debate has specific turn
     */
    protected function assertDebateTurn(Debate $debate, int $expectedTurn): void
    {
        $this->assertEquals(
            $expectedTurn,
            $debate->current_turn,
            "Debate should be on turn {$expectedTurn}"
        );
    }

    /**
     * Assert debate has participants
     */
    protected function assertDebateParticipants(Debate $debate, User $expectedAffirmative, User $expectedNegative): void
    {
        $this->assertEquals(
            $expectedAffirmative->id,
            $debate->affirmative_user_id,
            'Debate affirmative user should match'
        );
        $this->assertEquals(
            $expectedNegative->id,
            $debate->negative_user_id,
            'Debate negative user should match'
        );
    }

    /**
     * Assert debate belongs to room
     */
    protected function assertDebateBelongsToRoom(Debate $debate, Room $expectedRoom): void
    {
        $this->assertEquals(
            $expectedRoom->id,
            $debate->room_id,
            'Debate should belong to the specified room'
        );
    }

    /**
     * Assert debate has messages
     */
    protected function assertDebateHasMessages(Debate $debate, int $expectedCount): void
    {
        $this->assertEquals(
            $expectedCount,
            $debate->messages()->count(),
            "Debate should have {$expectedCount} messages"
        );
    }

    /**
     * Assert debate has evaluation
     */
    protected function assertDebateHasEvaluation(Debate $debate): void
    {
        $this->assertNotNull($debate->evaluations, 'Debate should have an evaluation');
    }

    /**
     * Assert debate evaluation winner
     */
    protected function assertDebateWinner(Debate $debate, string $expectedWinner): void
    {
        $this->assertNotNull($debate->evaluations, 'Debate should have an evaluation');
        $this->assertEquals(
            $expectedWinner,
            $debate->evaluations->winner,
            "Debate winner should be {$expectedWinner}"
        );
    }

    /**
     * Assert user can request early termination
     */
    protected function assertCanRequestEarlyTermination(Debate $debate, User $user): void
    {
        $this->assertTrue(
            $debate->canRequestEarlyTermination($user->id),
            'User should be able to request early termination'
        );
    }

    /**
     * Assert user cannot request early termination
     */
    protected function assertCannotRequestEarlyTermination(Debate $debate, User $user): void
    {
        $this->assertFalse(
            $debate->canRequestEarlyTermination($user->id),
            'User should not be able to request early termination'
        );
    }

    /**
     * Assert user can respond to early termination
     */
    protected function assertCanRespondToEarlyTermination(Debate $debate, User $user): void
    {
        $this->assertTrue(
            $debate->canRespondToEarlyTermination($user->id),
            'User should be able to respond to early termination'
        );
    }

    /**
     * Assert user cannot respond to early termination
     */
    protected function assertCannotRespondToEarlyTermination(Debate $debate, User $user): void
    {
        $this->assertFalse(
            $debate->canRespondToEarlyTermination($user->id),
            'User should not be able to respond to early termination'
        );
    }

    /**
     * Assert debate turn end time
     */
    protected function assertDebateTurnEndTime(Debate $debate, \DateTime $expectedTime): void
    {
        $this->assertEquals(
            $expectedTime->format('Y-m-d H:i:s'),
            $debate->turn_end_time->format('Y-m-d H:i:s'),
            'Debate turn end time should match expected time'
        );
    }

    /**
     * Assert debate is AI debate
     */
    protected function assertIsAIDebate(Debate $debate): void
    {
        $aiUserId = config('app.ai_user_id', 1);
        $this->assertTrue(
            $debate->affirmative_user_id == $aiUserId || $debate->negative_user_id == $aiUserId,
            'Debate should involve AI user'
        );
    }
}
