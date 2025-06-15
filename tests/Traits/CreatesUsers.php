<?php

namespace Tests\Traits;

use App\Models\User;

trait CreatesUsers
{
    /**
     * Create a basic user
     */
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Create an admin user
     */
    protected function createAdmin(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    /**
     * Create a guest user
     */
    protected function createGuest(array $attributes = []): User
    {
        return User::factory()->guest()->create($attributes);
    }

    /**
     * Create an expired guest user
     */
    protected function createExpiredGuest(array $attributes = []): User
    {
        return User::factory()->expiredGuest()->create($attributes);
    }

    /**
     * Create a verified user
     */
    protected function createVerifiedUser(array $attributes = []): User
    {
        return User::factory()->verified()->create($attributes);
    }

    /**
     * Create an unverified user
     */
    protected function createUnverifiedUser(array $attributes = []): User
    {
        return User::factory()->unverified()->create($attributes);
    }

    /**
     * Create multiple users
     */
    protected function createUsers(int $count = 3, array $attributes = []): \Illuminate\Database\Eloquent\Collection
    {
        return User::factory()->count($count)->create($attributes);
    }

    /**
     * Create user with debates
     */
    protected function createUserWithDebates(int $debateCount = 3, array $attributes = []): User
    {
        return User::factory()->withDebates($debateCount)->create($attributes);
    }

    /**
     * Create user with specific win/loss record
     */
    protected function createUserWithRecord(int $wins = 3, int $total = 5, array $attributes = []): User
    {
        return User::factory()->withDebateCount($wins, $total)->create($attributes);
    }

    /**
     * Create a pair of users for debates
     */
    protected function createDebatePair(array $userAAttributes = [], array $userBAttributes = []): array
    {
        return [
            'userA' => $this->createUser($userAAttributes),
            'userB' => $this->createUser($userBAttributes),
        ];
    }

    /**
     * Create admin and regular user pair
     */
    protected function createAdminAndUser(): array
    {
        return [
            'admin' => $this->createAdmin(),
            'user' => $this->createUser(),
        ];
    }

    /**
     * Create users with different verification statuses
     */
    protected function createUsersWithVariousStates(): array
    {
        return [
            'verified' => $this->createVerifiedUser(),
            'unverified' => $this->createUnverifiedUser(),
            'admin' => $this->createAdmin(),
            'guest' => $this->createGuest(),
            'expired_guest' => $this->createExpiredGuest(),
        ];
    }

    /**
     * Create user and act as that user
     */
    protected function createAndActAsUser(array $attributes = []): User
    {
        $user = $this->createUser($attributes);
        $this->actingAs($user);
        return $user;
    }

    /**
     * Create admin and act as that admin
     */
    protected function createAndActAsAdmin(array $attributes = []): User
    {
        $admin = $this->createAdmin($attributes);
        $this->actingAs($admin);
        return $admin;
    }

    /**
     * Create guest and act as that guest
     */
    protected function createAndActAsGuest(array $attributes = []): User
    {
        $guest = $this->createGuest($attributes);
        $this->actingAs($guest);
        return $guest;
    }

    /**
     * Assert user has specific attributes
     */
    protected function assertUserHasAttributes(User $user, array $expectedAttributes): void
    {
        foreach ($expectedAttributes as $attribute => $value) {
            $this->assertEquals(
                $value,
                $user->getAttribute($attribute),
                "User attribute '{$attribute}' does not match expected value"
            );
        }
    }

    /**
     * Assert user is of specific type
     */
    protected function assertUserType(User $user, string $type): void
    {
        switch ($type) {
            case 'admin':
                $this->assertTrue($user->isAdmin(), 'User should be admin');
                break;
            case 'guest':
                $this->assertTrue($user->isGuest(), 'User should be guest');
                break;
            case 'verified':
                $this->assertNotNull($user->email_verified_at, 'User should be verified');
                break;
            case 'unverified':
                $this->assertNull($user->email_verified_at, 'User should be unverified');
                break;
            case 'expired_guest':
                $this->assertTrue($user->isGuest(), 'User should be guest');
                $this->assertTrue($user->isGuestExpired(), 'Guest should be expired');
                break;
            default:
                $this->fail("Unknown user type: {$type}");
        }
    }
}
