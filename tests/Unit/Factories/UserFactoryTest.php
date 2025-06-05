<?php

namespace Tests\Unit\Factories;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use App\Models\DebateEvaluation;
use Carbon\Carbon;

class UserFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_basic_user()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->name);
        $this->assertNotNull($user->email);
        $this->assertNotNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->is_admin);
        $this->assertFalse($user->is_guest);
    }

    /** @test */
    public function it_creates_unverified_user()
    {
        $user = User::factory()->unverified()->create();

        $this->assertNull($user->email_verified_at);
    }

    /** @test */
    public function it_creates_admin_user()
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->is_admin);
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test */
    public function it_creates_guest_user()
    {
        $user = User::factory()->guest()->create();

        $this->assertTrue($user->is_guest);
        $this->assertNotNull($user->guest_expires_at);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->password);
        $this->assertTrue($user->guest_expires_at->greaterThan(now()));
    }

    /** @test */
    public function it_creates_verified_user()
    {
        $user = User::factory()->verified()->create();

        $this->assertNotNull($user->email_verified_at);
    }

    /** @test */
    public function it_creates_expired_guest_user()
    {
        $user = User::factory()->expiredGuest()->create();

        $this->assertTrue($user->is_guest);
        $this->assertNotNull($user->guest_expires_at);
        $this->assertTrue($user->guest_expires_at->lessThan(now()));
        $this->assertTrue($user->isGuestExpired());
    }

    /** @test */
    public function it_creates_user_with_debates()
    {
        $user = User::factory()->withDebates(2)->create();

        $this->assertEquals(2, $user->rooms()->count());

        $rooms = $user->rooms;
        foreach ($rooms as $room) {
            $this->assertNotNull($room->debate);
            $this->assertEquals($user->id, $room->debate->affirmative_user_id);
        }
    }

    /** @test */
    public function it_creates_user_with_specific_debate_count_and_wins()
    {
        $user = User::factory()->withDebateCount(3, 5)->create();

        // Check total debates
        $debates = Debate::where('affirmative_user_id', $user->id)
            ->orWhere('negative_user_id', $user->id)
            ->get();

        $this->assertEquals(5, $debates->count());

        // Check wins (should be 3 wins as affirmative)
        $wins = DebateEvaluation::whereIn('debate_id', $debates->pluck('id'))
            ->where('winner', 'affirmative')
            ->count();

        $this->assertEquals(3, $wins);

        // Check losses
        $losses = DebateEvaluation::whereIn('debate_id', $debates->pluck('id'))
            ->where('winner', 'negative')
            ->count();

        $this->assertEquals(2, $losses);
    }

    /** @test */
    public function it_can_combine_states()
    {
        $user = User::factory()->admin()->verified()->create();

        $this->assertTrue($user->is_admin);
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test */
    public function guest_user_validation_methods_work()
    {
        $validGuest = User::factory()->guest()->create();
        $expiredGuest = User::factory()->expiredGuest()->create();

        $this->assertTrue($validGuest->isGuest());
        $this->assertFalse($validGuest->isGuestExpired());
        $this->assertTrue($validGuest->isGuestValid());

        $this->assertTrue($expiredGuest->isGuest());
        $this->assertTrue($expiredGuest->isGuestExpired());
        $this->assertFalse($expiredGuest->isGuestValid());
    }

    /** @test */
    public function admin_user_has_admin_privileges()
    {
        $admin = User::factory()->admin()->create();
        $regular = User::factory()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($regular->isAdmin());
    }

    /** @test */
    public function factory_handles_custom_attributes()
    {
        $customName = 'Custom Test User';
        $customEmail = 'custom@test.com';

        $user = User::factory()->create([
            'name' => $customName,
            'email' => $customEmail,
        ]);

        $this->assertEquals($customName, $user->name);
        $this->assertEquals($customEmail, $user->email);
    }

    /** @test */
    public function factory_creates_unique_emails()
    {
        $users = User::factory()->count(5)->create();
        $emails = $users->pluck('email')->toArray();

        $this->assertEquals(5, count(array_unique($emails)));
    }
}
