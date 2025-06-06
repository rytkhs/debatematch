<?php

namespace Database\Factories;

use App\Models\DebateMessage;
use App\Models\Debate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DebateMessage>
 */
class DebateMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'debate_id' => Debate::factory(),
            'user_id' => User::factory(),
            'message' => fake()->paragraph(),
            'turn' => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * Create a message for a specific debate.
     */
    public function forDebate(Debate $debate): static
    {
        return $this->state(fn(array $attributes) => [
            'debate_id' => $debate->id,
        ]);
    }

    /**
     * Create a message for a specific user.
     */
    public function fromUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a message for affirmative side.
     */
    public function affirmative(): static
    {
        return $this->afterMaking(function (DebateMessage $message) {
            if ($message->debate) {
                $message->user_id = $message->debate->affirmative_user_id;
            }
        });
    }

    /**
     * Create a message for negative side.
     */
    public function negative(): static
    {
        return $this->afterMaking(function (DebateMessage $message) {
            if ($message->debate) {
                $message->user_id = $message->debate->negative_user_id;
            }
        });
    }

    /**
     * Create a message for a specific turn.
     */
    public function onTurn(int $turn): static
    {
        return $this->state(fn(array $attributes) => [
            'turn' => $turn,
        ]);
    }

    /**
     * Create a short message.
     */
    public function short(): static
    {
        return $this->state(fn(array $attributes) => [
            'message' => fake()->sentence(),
        ]);
    }

    /**
     * Create a long message.
     */
    public function long(): static
    {
        return $this->state(fn(array $attributes) => [
            'message' => fake()->paragraphs(3, true),
        ]);
    }

    /**
     * Create an opening statement message.
     */
    public function openingStatement(): static
    {
        return $this->state(fn(array $attributes) => [
            'message' => fake()->paragraphs(2, true) . ' This is our opening statement on the topic.',
            'turn' => 0,
        ]);
    }

    /**
     * Create a rebuttal message.
     */
    public function rebuttal(): static
    {
        return $this->state(fn(array $attributes) => [
            'message' => 'I disagree with the previous statement because ' . fake()->paragraph(),
            'turn' => fake()->numberBetween(1, 3),
        ]);
    }

    /**
     * Create a closing statement message.
     */
    public function closingStatement(): static
    {
        return $this->state(fn(array $attributes) => [
            'message' => 'In conclusion, ' . fake()->paragraph() . ' Thank you.',
            'turn' => fake()->numberBetween(4, 8),
        ]);
    }

    /**
     * Create a message with evidence reference.
     */
    public function withEvidence(): static
    {
        return $this->state(fn(array $attributes) => [
            'message' => fake()->paragraph() . ' According to research by ' . fake()->name() . ', ' . fake()->sentence(),
        ]);
    }

    /**
     * Create a message in Japanese.
     */
    public function japanese(): static
    {
        return $this->state(fn(array $attributes) => [
            'message' => 'これは日本語のディベートメッセージです。' . fake()->realText(200),
        ]);
    }

    /**
     * Create a message in English.
     */
    public function english(): static
    {
        return $this->state(fn(array $attributes) => [
            'message' => fake()->paragraph(),
        ]);
    }

    /**
     * Create a conversation sequence of messages for a debate.
     */
    public function conversation(Debate $debate, int $count = 5): array
    {
        $messages = [];
        $users = [$debate->affirmative_user_id, $debate->negative_user_id];

        for ($i = 0; $i < $count; $i++) {
            $messages[] = $this->create([
                'debate_id' => $debate->id,
                'user_id' => $users[$i % 2],
                'turn' => $i,
                'message' => $this->generateContextualMessage($i),
            ]);
        }

        return $messages;
    }

    /**
     * Generate contextual message based on turn number.
     */
    private function generateContextualMessage(int $turn): string
    {
        switch ($turn) {
            case 0:
                return 'Opening statement: ' . fake()->paragraph();
            case 1:
                return 'Counter-argument: ' . fake()->paragraph();
            default:
                return fake()->paragraph();
        }
    }
}
