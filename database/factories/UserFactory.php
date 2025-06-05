<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_admin' => false,
            'is_guest' => false,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_admin' => true,
            'is_guest' => false,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Create a guest user.
     */
    public function guest(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_admin' => false,
            'is_guest' => true,
            'guest_expires_at' => now()->addDays(7),
            'email_verified_at' => null,
            'password' => null,
        ]);
    }

    /**
     * Create a verified user.
     */
    public function verified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Create a user with expired guest status.
     */
    public function expiredGuest(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_admin' => false,
            'is_guest' => true,
            'guest_expires_at' => now()->subDays(1),
            'email_verified_at' => null,
            'password' => null,
        ]);
    }

    /**
     * Create a user with debates relation.
     */
    public function withDebates(int $count = 3): static
    {
        return $this->afterCreating(function ($user) use ($count) {
            // Create rooms and debates for the user
            $rooms = \App\Models\Room::factory($count)->create(['created_by' => $user->id]);

            foreach ($rooms as $room) {
                // Attach user to room
                $room->users()->attach($user->id, ['side' => 'affirmative']);

                // Create opponent
                $opponent = \App\Models\User::factory()->create();
                $room->users()->attach($opponent->id, ['side' => 'negative']);

                // Create debate
                \App\Models\Debate::factory()->create([
                    'room_id' => $room->id,
                    'affirmative_user_id' => $user->id,
                    'negative_user_id' => $opponent->id,
                ]);
            }
        });
    }

    /**
     * Create a user with specific debate count.
     */
    public function withDebateCount(int $wins = 0, int $total = 5): static
    {
        return $this->afterCreating(function ($user) use ($wins, $total) {
            for ($i = 0; $i < $total; $i++) {
                $room = \App\Models\Room::factory()->create(['created_by' => $user->id]);
                $opponent = \App\Models\User::factory()->create();

                $room->users()->attach($user->id, ['side' => 'affirmative']);
                $room->users()->attach($opponent->id, ['side' => 'negative']);

                $debate = \App\Models\Debate::factory()->create([
                    'room_id' => $room->id,
                    'affirmative_user_id' => $user->id,
                    'negative_user_id' => $opponent->id,
                ]);

                // Create evaluation for wins
                if ($i < $wins) {
                    \App\Models\DebateEvaluation::factory()->create([
                        'debate_id' => $debate->id,
                        'winner' => 'affirmative',
                        'is_analyzable' => true,
                    ]);
                } else {
                    \App\Models\DebateEvaluation::factory()->create([
                        'debate_id' => $debate->id,
                        'winner' => 'negative',
                        'is_analyzable' => true,
                    ]);
                }
            }
        });
    }
}
