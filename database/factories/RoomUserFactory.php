<?php

namespace Database\Factories;

use App\Models\RoomUser;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoomUser>
 */
class RoomUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RoomUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'user_id' => User::factory(),
            'side' => fake()->randomElement([
                RoomUser::SIDE_AFFIRMATIVE,
                RoomUser::SIDE_NEGATIVE
            ]),
        ];
    }

    /**
     * Create a room user for specific room and user.
     */
    public function forRoomAndUser(Room $room, User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'room_id' => $room->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a room user on affirmative side.
     */
    public function affirmative(): static
    {
        return $this->state(fn(array $attributes) => [
            'side' => RoomUser::SIDE_AFFIRMATIVE,
        ]);
    }

    /**
     * Create a room user on negative side.
     */
    public function negative(): static
    {
        return $this->state(fn(array $attributes) => [
            'side' => RoomUser::SIDE_NEGATIVE,
        ]);
    }

    /**
     * Create a room user who is the room creator.
     */
    public function creator(): static
    {
        return $this->afterMaking(function (RoomUser $roomUser) {
            // Get the room and set the user as creator
            $room = $roomUser->room ?? Room::find($roomUser->room_id);
            if ($room) {
                $room->update(['created_by' => $roomUser->user_id]);
            }
        });
    }

    /**
     * Create a room user for a specific room.
     */
    public function forRoom(Room $room): static
    {
        return $this->state(fn(array $attributes) => [
            'room_id' => $room->id,
        ]);
    }

    /**
     * Create a room user for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create an affirmative room user who is also the creator.
     */
    public function affirmativeCreator(): static
    {
        return $this->affirmative()->creator();
    }

    /**
     * Create a negative room user who is also the creator.
     */
    public function negativeCreator(): static
    {
        return $this->negative()->creator();
    }

    /**
     * Create a room user with a guest user.
     */
    public function guestUser(): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => User::factory()->guest()->create()->id,
        ]);
    }

    /**
     * Create a room user with an admin user.
     */
    public function adminUser(): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => User::factory()->admin()->create()->id,
        ]);
    }

    /**
     * Create a balanced room with both affirmative and negative users.
     */
    public function balanced(Room $room): array
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        return [
            $this->create([
                'room_id' => $room->id,
                'user_id' => $affirmativeUser->id,
                'side' => RoomUser::SIDE_AFFIRMATIVE,
            ]),
            $this->create([
                'room_id' => $room->id,
                'user_id' => $negativeUser->id,
                'side' => RoomUser::SIDE_NEGATIVE,
            ]),
        ];
    }

    /**
     * Create multiple room users for the same room.
     */
    public function forSameRoom(Room $room, int $affirmativeCount = 1, int $negativeCount = 1): array
    {
        $roomUsers = [];

        // Create affirmative users
        for ($i = 0; $i < $affirmativeCount; $i++) {
            $roomUsers[] = $this->create([
                'room_id' => $room->id,
                'user_id' => User::factory()->create()->id,
                'side' => RoomUser::SIDE_AFFIRMATIVE,
            ]);
        }

        // Create negative users
        for ($i = 0; $i < $negativeCount; $i++) {
            $roomUsers[] = $this->create([
                'room_id' => $room->id,
                'user_id' => User::factory()->create()->id,
                'side' => RoomUser::SIDE_NEGATIVE,
            ]);
        }

        return $roomUsers;
    }

    /**
     * Create room users for AI debate scenario.
     */
    public function aiDebateScenario(Room $room): array
    {
        $humanUser = User::factory()->create();
        $aiUser = User::factory()->guest()->create([
            'name' => 'AI Assistant',
            'email' => 'ai@debatematch.com',
        ]);

        $room->update(['is_ai_debate' => true]);

        return [
            $this->create([
                'room_id' => $room->id,
                'user_id' => $humanUser->id,
                'side' => RoomUser::SIDE_AFFIRMATIVE,
            ]),
            $this->create([
                'room_id' => $room->id,
                'user_id' => $aiUser->id,
                'side' => RoomUser::SIDE_NEGATIVE,
            ]),
        ];
    }
}
