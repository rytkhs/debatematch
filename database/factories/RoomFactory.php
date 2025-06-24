<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\User;
use App\Models\Debate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'topic' => fake()->sentence(),
            'remarks' => fake()->optional()->paragraph(),
            'status' => Room::STATUS_WAITING,
            'created_by' => User::factory(),
            'language' => fake()->randomElement(['japanese', 'english']),
            'format_type' => 'format_name_nada_high', // デフォルトで有効なフォーマットを使用
            'custom_format_settings' => null,
            'evidence_allowed' => fake()->boolean(),
            'is_ai_debate' => false,
        ];
    }

    /**
     * Create a room with waiting status.
     */
    public function waiting(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Room::STATUS_WAITING,
        ]);
    }

    /**
     * Create a room with ready status.
     */
    public function ready(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Room::STATUS_READY,
        ]);
    }

    /**
     * Create a room with debating status.
     */
    public function debating(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Room::STATUS_DEBATING,
        ]);
    }

    /**
     * Create a room with finished status.
     */
    public function finished(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Room::STATUS_FINISHED,
        ]);
    }

    /**
     * Create a room with terminated status.
     */
    public function terminated(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Room::STATUS_TERMINATED,
        ]);
    }

    /**
     * Create a room with AI debate enabled.
     */
    public function aiDebate(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_ai_debate' => true,
        ]);
    }

    /**
     * Create a room with custom format.
     */
    public function customFormat(): static
    {
        return $this->state(fn(array $attributes) => [
            'format_type' => 'custom',
            'custom_format_settings' => [
                [
                    'name' => 'Opening Statement',
                    'time_limit' => 300,
                    'side' => 'affirmative'
                ],
                [
                    'name' => 'Rebuttal',
                    'time_limit' => 240,
                    'side' => 'negative'
                ],
                [
                    'name' => 'Closing Statement',
                    'time_limit' => 180,
                    'side' => 'affirmative'
                ]
            ],
        ]);
    }

    /**
     * Create a room with free format.
     */
    public function freeFormat(): static
    {
        return $this->state(fn(array $attributes) => [
            'format_type' => 'free',
            'custom_format_settings' => [],
        ]);
    }

    /**
     * Create a room with users attached.
     */
    public function withUsers(int $affirmativeCount = 1, int $negativeCount = 1): static
    {
        return $this->afterCreating(function (Room $room) use ($affirmativeCount, $negativeCount) {
            // Create affirmative users
            $affirmativeUsers = User::factory($affirmativeCount)->create();
            foreach ($affirmativeUsers as $user) {
                $room->users()->attach($user->id, ['side' => 'affirmative']);
            }

            // Create negative users
            $negativeUsers = User::factory($negativeCount)->create();
            foreach ($negativeUsers as $user) {
                $room->users()->attach($user->id, ['side' => 'negative']);
            }
        });
    }

    /**
     * Create a room with a debate.
     */
    public function withDebate(): static
    {
        return $this->afterCreating(function (Room $room) {
            // Ensure room has users for debate
            if ($room->users()->count() === 0) {
                $affirmativeUser = User::factory()->create();
                $negativeUser = User::factory()->create();
                $room->users()->attach($affirmativeUser->id, ['side' => 'affirmative']);
                $room->users()->attach($negativeUser->id, ['side' => 'negative']);
            } else {
                $affirmativeUser = $room->users()->wherePivot('side', 'affirmative')->first();
                $negativeUser = $room->users()->wherePivot('side', 'negative')->first();
            }

            Debate::factory()->create([
                'room_id' => $room->id,
                'affirmative_user_id' => $affirmativeUser?->id ?? User::factory()->create()->id,
                'negative_user_id' => $negativeUser?->id ?? User::factory()->create()->id,
            ]);
        });
    }

    /**
     * Create a room with specific creator.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Create a room with evidence allowed.
     */
    public function withEvidence(): static
    {
        return $this->state(fn(array $attributes) => [
            'evidence_allowed' => true,
        ]);
    }

    /**
     * Create a room without evidence allowed.
     */
    public function withoutEvidence(): static
    {
        return $this->state(fn(array $attributes) => [
            'evidence_allowed' => false,
        ]);
    }

    /**
     * Create a room with specific language.
     */
    public function inLanguage(string $language): static
    {
        return $this->state(fn(array $attributes) => [
            'language' => $language,
        ]);
    }

    /**
     * Create a Japanese room.
     */
    public function japanese(): static
    {
        return $this->inLanguage('japanese');
    }

    /**
     * Create an English room.
     */
    public function english(): static
    {
        return $this->inLanguage('english');
    }
}
