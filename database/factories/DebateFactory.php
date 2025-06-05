<?php

namespace Database\Factories;

use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use App\Models\DebateMessage;
use App\Models\DebateEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Debate>
 */
class DebateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'affirmative_user_id' => User::factory(),
            'negative_user_id' => User::factory(),
            'current_turn' => 0,
            'turn_end_time' => null,
        ];
    }

    /**
     * Create a debate for a specific room.
     */
    public function forRoom(Room $room): static
    {
        return $this->state(fn(array $attributes) => [
            'room_id' => $room->id,
        ]);
    }

    /**
     * Create a debate with specific users.
     */
    public function withUsers(User $affirmativeUser, User $negativeUser): static
    {
        return $this->state(fn(array $attributes) => [
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);
    }

    /**
     * Create a debate with ongoing turn.
     */
    public function withCurrentTurn(int $turn): static
    {
        return $this->state(fn(array $attributes) => [
            'current_turn' => $turn,
            'turn_end_time' => now()->addMinutes(fake()->numberBetween(1, 10)),
        ]);
    }

    /**
     * Create a debate with finished turn.
     */
    public function finished(): static
    {
        return $this->state(fn(array $attributes) => [
            'current_turn' => -1, // -1 indicates finished
            'turn_end_time' => null,
        ]);
    }

    /**
     * Create a debate with messages.
     */
    public function withMessages(int $count = 5): static
    {
        return $this->afterCreating(function (Debate $debate) use ($count) {
            $users = [$debate->affirmative_user_id, $debate->negative_user_id];

            for ($i = 0; $i < $count; $i++) {
                DebateMessage::factory()->create([
                    'debate_id' => $debate->id,
                    'user_id' => $users[$i % 2], // Alternate between users
                    'turn' => $i,
                ]);
            }
        });
    }

    /**
     * Create a debate with specific messages pattern.
     */
    public function withMessagesPattern(array $pattern): static
    {
        return $this->afterCreating(function (Debate $debate) use ($pattern) {
            foreach ($pattern as $index => $messageData) {
                DebateMessage::factory()->create([
                    'debate_id' => $debate->id,
                    'user_id' => $messageData['user_id'] ?? $debate->affirmative_user_id,
                    'turn' => $messageData['turn'] ?? $index,
                    'message' => $messageData['message'] ?? fake()->paragraph(),
                ]);
            }
        });
    }

    /**
     * Create a debate with evaluation.
     */
    public function withEvaluation(string $winner = null): static
    {
        return $this->afterCreating(function (Debate $debate) use ($winner) {
            DebateEvaluation::factory()->create([
                'debate_id' => $debate->id,
                'winner' => $winner ?? fake()->randomElement(['affirmative', 'negative']),
            ]);
        });
    }

    /**
     * Create a debate with winning evaluation for affirmative.
     */
    public function affirmativeWins(): static
    {
        return $this->withEvaluation(DebateEvaluation::WINNER_AFFIRMATIVE);
    }

    /**
     * Create a debate with winning evaluation for negative.
     */
    public function negativeWins(): static
    {
        return $this->withEvaluation(DebateEvaluation::WINNER_NEGATIVE);
    }

    /**
     * Create a debate with complete scenario (messages and evaluation).
     */
    public function complete(int $messageCount = 8, string $winner = null): static
    {
        return $this->withMessages($messageCount)->withEvaluation($winner);
    }

    /**
     * Create a debate with AI user.
     */
    public function withAI(): static
    {
        return $this->afterCreating(function (Debate $debate) {
            // Update room to be AI debate
            $debate->room->update(['is_ai_debate' => true]);

            // Create some AI messages
            DebateMessage::factory(3)->create([
                'debate_id' => $debate->id,
                'user_id' => $debate->negative_user_id, // Assume AI is negative side
                'turn' => fake()->numberBetween(1, 5),
            ]);
        });
    }

    /**
     * Create a debate with early termination scenario.
     */
    public function withEarlyTermination(): static
    {
        return $this->state(fn(array $attributes) => [
            'current_turn' => fake()->numberBetween(1, 3), // Early termination
        ])->afterCreating(function (Debate $debate) {
            // Add some messages before termination
            DebateMessage::factory(2)->create([
                'debate_id' => $debate->id,
                'user_id' => $debate->affirmative_user_id,
                'turn' => 0,
            ]);

            DebateMessage::factory(2)->create([
                'debate_id' => $debate->id,
                'user_id' => $debate->negative_user_id,
                'turn' => 1,
            ]);
        });
    }
}
