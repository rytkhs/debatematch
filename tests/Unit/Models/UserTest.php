<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use App\Models\DebateEvaluation;
use Tests\Traits\CreatesUsers;
use Tests\Traits\CreatesRooms;
use Tests\Traits\CreatesDebates;
use Tests\Traits\AssertsDatabaseState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UserTest extends BaseModelTest
{
    use RefreshDatabase, CreatesUsers, CreatesRooms, CreatesDebates, AssertsDatabaseState;

    protected string $modelClass = User::class;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // ============================================
    // TODO-006: User モデル基本機能テスト
    // ============================================

    /**
     * @test
     */
    public function testFillableAttributes()
    {
        $expectedFillable = [
            'name',
            'email',
            'password',
            'is_admin',
            'google_id',
            'email_verified_at',
            'is_guest',
            'guest_expires_at',
        ];

        $this->assertModelBasics($expectedFillable);
    }

    /**
     * @test
     */
    public function testHiddenAttributes()
    {
        $expectedHidden = [
            'password',
            'remember_token',
        ];

        $this->assertModelBasics(null, $expectedHidden);
    }

    /**
     * @test
     */
    public function testCasts()
    {
        $expectedCasts = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'deleted_at' => 'datetime',
            'guest_expires_at' => 'datetime',
            'id' => 'int',
        ];

        $this->assertModelBasics(null, null, $expectedCasts);
    }

    /**
     * @test
     */
    public function testEmailValidation()
    {
        // Valid email
        $user = User::factory()->make(['email' => 'test@example.com']);
        $this->assertNotNull($user->email);

        // Invalid email would be caught by database constraints or application validation
        // This tests the model's ability to handle email attributes
        $user->email = 'invalid-email';
        $this->assertEquals('invalid-email', $user->email);
    }

    /**
     * @test
     */
    public function testPasswordHashing()
    {
        $plainPassword = 'test-password';
        $user = User::factory()->create(['password' => $plainPassword]);

        // Password should be hashed
        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }

    /**
     * @test
     */
    public function testFactoryCreation()
    {
        $this->assertFactoryCreation();
    }

    /**
     * @test
     */
    public function testSoftDeletes()
    {
        $this->assertSoftDeletes();
    }

    // ============================================
    // TODO-007: User リレーションシップテスト
    // ============================================

    /**
     * @test
     */
    public function testRoomsRelation()
    {
        $this->assertBelongsToMany('rooms', Room::class);

        // Test actual relationship with data
        $user = User::factory()->create();
        $room = Room::factory()->create();

        // Attach user to room with pivot data
        $user->rooms()->attach($room->id, ['side' => 'affirmative']);

        $this->assertEquals(1, $user->rooms()->count());
        $this->assertEquals($room->id, $user->rooms->first()->id);
        $this->assertEquals('affirmative', $user->rooms->first()->pivot->side);
    }

    /**
     * @test
     */
    public function testAffirmativeDebatesRelation()
    {
        $this->assertHasMany('affirmativeDebates', Debate::class);

        // Test actual relationship with data
        $user = User::factory()->create();
        $opponent = User::factory()->create();
        $room = Room::factory()->create();

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $user->id,
            'negative_user_id' => $opponent->id,
        ]);

        $this->assertEquals(1, $user->affirmativeDebates()->count());
        $this->assertEquals($debate->id, $user->affirmativeDebates->first()->id);
        $this->assertEquals(0, $opponent->affirmativeDebates()->count());
    }

    /**
     * @test
     */
    public function testNegativeDebatesRelation()
    {
        $this->assertHasMany('negativeDebates', Debate::class);

        // Test actual relationship with data
        $user = User::factory()->create();
        $opponent = User::factory()->create();
        $room = Room::factory()->create();

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $opponent->id,
            'negative_user_id' => $user->id,
        ]);

        $this->assertEquals(1, $user->negativeDebates()->count());
        $this->assertEquals($debate->id, $user->negativeDebates->first()->id);
        $this->assertEquals(0, $opponent->negativeDebates()->count());
    }

    /**
     * @test
     */
    public function testRelationshipCounts()
    {
        $user = User::factory()->create();
        $opponent = User::factory()->create();

        // Create rooms and debates
        $room1 = Room::factory()->create();
        $room2 = Room::factory()->create();

        // User participates in 2 rooms
        $user->rooms()->attach($room1->id, ['side' => 'affirmative']);
        $user->rooms()->attach($room2->id, ['side' => 'negative']);

        // User has 1 affirmative debate and 1 negative debate
        Debate::factory()->create([
            'room_id' => $room1->id,
            'affirmative_user_id' => $user->id,
            'negative_user_id' => $opponent->id,
        ]);

        Debate::factory()->create([
            'room_id' => $room2->id,
            'affirmative_user_id' => $opponent->id,
            'negative_user_id' => $user->id,
        ]);

        $this->assertEquals(2, $user->rooms()->count());
        $this->assertEquals(1, $user->affirmativeDebates()->count());
        $this->assertEquals(1, $user->negativeDebates()->count());
    }

    /**
     * @test
     */
    public function testEagerLoading()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $opponent = User::factory()->create();

        // Attach user to room
        $user->rooms()->attach($room->id, ['side' => 'affirmative']);

        // Create debate
        Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $user->id,
            'negative_user_id' => $opponent->id,
        ]);

        // Test eager loading
        $userWithRelations = User::with(['rooms', 'affirmativeDebates', 'negativeDebates'])
            ->find($user->id);

        $this->assertTrue($userWithRelations->relationLoaded('rooms'));
        $this->assertTrue($userWithRelations->relationLoaded('affirmativeDebates'));
        $this->assertTrue($userWithRelations->relationLoaded('negativeDebates'));

        $this->assertEquals(1, $userWithRelations->rooms->count());
        $this->assertEquals(1, $userWithRelations->affirmativeDebates->count());
        $this->assertEquals(0, $userWithRelations->negativeDebates->count());
    }

    /**
     * @test
     */
    public function testRelationshipPivotData()
    {
        $user = User::factory()->create();
        $room1 = Room::factory()->create();
        $room2 = Room::factory()->create();

        // Attach with different sides
        $user->rooms()->attach($room1->id, ['side' => 'affirmative']);
        $user->rooms()->attach($room2->id, ['side' => 'negative']);

        $affirmativeRoom = $user->rooms()->wherePivot('side', 'affirmative')->first();
        $negativeRoom = $user->rooms()->wherePivot('side', 'negative')->first();

        $this->assertEquals($room1->id, $affirmativeRoom->id);
        $this->assertEquals('affirmative', $affirmativeRoom->pivot->side);

        $this->assertEquals($room2->id, $negativeRoom->id);
        $this->assertEquals('negative', $negativeRoom->pivot->side);
    }

    /**
     * @test
     */
    public function testRelationshipWithTrashedModels()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $opponent = User::factory()->create();

        // Create debate
        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $user->id,
            'negative_user_id' => $opponent->id,
        ]);

        // Soft delete the opponent
        $opponent->delete();

        // User should still have access to affirmative debates
        $this->assertEquals(1, $user->affirmativeDebates()->count());

        // Test with trashed opponent
        $debateWithTrashedUser = $user->affirmativeDebates()
            ->with(['negativeUser' => function ($query) {
                $query->withTrashed();
            }])
            ->first();

        $this->assertNotNull($debateWithTrashedUser->negativeUser);
        $this->assertTrue($debateWithTrashedUser->negativeUser->trashed());
    }

    // ============================================
    // TODO-008: User 統計機能テスト
    // ============================================

    /**
     * @test
     */
    public function testGetDebatesCountAttribute()
    {
        $user = User::factory()->create();
        $opponent = User::factory()->create();

        // Initially no debates
        $this->assertEquals(0, $user->debates_count);

        // Create debates where user is affirmative
        $room1 = Room::factory()->create();
        Debate::factory()->create([
            'room_id' => $room1->id,
            'affirmative_user_id' => $user->id,
            'negative_user_id' => $opponent->id,
        ]);

        // Create debates where user is negative
        $room2 = Room::factory()->create();
        Debate::factory()->create([
            'room_id' => $room2->id,
            'affirmative_user_id' => $opponent->id,
            'negative_user_id' => $user->id,
        ]);

        // Fresh the model to clear cached attributes
        $user = $user->fresh();
        $this->assertEquals(2, $user->debates_count);

        // Opponent should have same count (participated in both debates)
        $opponent = $opponent->fresh();
        $this->assertEquals(2, $opponent->debates_count);
    }

    /**
     * @test
     */
    public function testGetWinsCountAttribute()
    {
        $user = User::factory()->create();
        $opponent = User::factory()->create();

        // Initially no wins
        $this->assertEquals(0, $user->wins_count);

        // Create debate where user wins as affirmative
        $room1 = Room::factory()->create();
        $debate1 = Debate::factory()->create([
            'room_id' => $room1->id,
            'affirmative_user_id' => $user->id,
            'negative_user_id' => $opponent->id,
        ]);

        DebateEvaluation::factory()->create([
            'debate_id' => $debate1->id,
            'winner' => 'affirmative',
        ]);

        // Create debate where user loses as negative
        $room2 = Room::factory()->create();
        $debate2 = Debate::factory()->create([
            'room_id' => $room2->id,
            'affirmative_user_id' => $opponent->id,
            'negative_user_id' => $user->id,
        ]);

        DebateEvaluation::factory()->create([
            'debate_id' => $debate2->id,
            'winner' => 'affirmative',
        ]);

        // Create debate where user wins as negative
        $room3 = Room::factory()->create();
        $debate3 = Debate::factory()->create([
            'room_id' => $room3->id,
            'affirmative_user_id' => $opponent->id,
            'negative_user_id' => $user->id,
        ]);

        DebateEvaluation::factory()->create([
            'debate_id' => $debate3->id,
            'winner' => 'negative',
        ]);

        // Fresh the model to clear cached attributes
        $user = $user->fresh();
        $this->assertEquals(2, $user->wins_count); // Won as affirmative and negative
    }

    /**
     * @test
     */
    public function testComplexStatisticsCalculation()
    {
        $user = User::factory()->create();

        // Create multiple opponents
        $opponents = User::factory(3)->create();

        $totalDebates = 0;
        $totalWins = 0;

        // Create various debate scenarios
        foreach ($opponents as $index => $opponent) {
            $room = Room::factory()->create();
            $debate = Debate::factory()->create([
                'room_id' => $room->id,
                'affirmative_user_id' => $user->id,
                'negative_user_id' => $opponent->id,
            ]);

            // User wins 2 out of 3 debates
            if ($index < 2) {
                DebateEvaluation::factory()->create([
                    'debate_id' => $debate->id,
                    'winner' => 'affirmative',
                ]);
                $totalWins++;
            } else {
                DebateEvaluation::factory()->create([
                    'debate_id' => $debate->id,
                    'winner' => 'negative',
                ]);
            }
            $totalDebates++;
        }

        $user = $user->fresh();
        $this->assertEquals($totalDebates, $user->debates_count);
        $this->assertEquals($totalWins, $user->wins_count);

        // Calculate win rate
        $expectedWinRate = $totalWins / $totalDebates;
        $actualWinRate = $user->wins_count / $user->debates_count;
        $this->assertEquals($expectedWinRate, $actualWinRate);
    }

    /**
     * @test
     */
    public function testStatisticsPerformance()
    {
        $user = User::factory()->create();
        $opponents = User::factory(10)->create();

        // Create 10 debates with evaluations
        foreach ($opponents as $opponent) {
            $room = Room::factory()->create();
            $debate = Debate::factory()->create([
                'room_id' => $room->id,
                'affirmative_user_id' => $user->id,
                'negative_user_id' => $opponent->id,
            ]);

            DebateEvaluation::factory()->create([
                'debate_id' => $debate->id,
                'winner' => 'affirmative',
            ]);
        }

        // Measure performance of statistics calculation
        $startTime = microtime(true);

        $user = $user->fresh();
        $debatesCount = $user->debates_count;
        $winsCount = $user->wins_count;

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertEquals(10, $debatesCount);
        $this->assertEquals(10, $winsCount);

        // Performance should be reasonable (less than 1 second)
        $this->assertLessThan(1.0, $executionTime);
    }

    /**
     * @test
     */
    public function testStatisticsWithoutEvaluations()
    {
        $user = User::factory()->create();
        $opponent = User::factory()->create();

        // Create debates without evaluations
        $room1 = Room::factory()->create();
        Debate::factory()->create([
            'room_id' => $room1->id,
            'affirmative_user_id' => $user->id,
            'negative_user_id' => $opponent->id,
        ]);

        $room2 = Room::factory()->create();
        Debate::factory()->create([
            'room_id' => $room2->id,
            'affirmative_user_id' => $opponent->id,
            'negative_user_id' => $user->id,
        ]);

        $user = $user->fresh();
        $this->assertEquals(2, $user->debates_count); // Has debates
        $this->assertEquals(0, $user->wins_count);   // No wins (no evaluations)
    }

    // ============================================
    // TODO-009: User ゲスト機能テスト
    // ============================================

    /**
     * @test
     */
    public function testIsGuest()
    {
        // Regular user should not be guest
        $regularUser = User::factory()->create(['is_guest' => false]);
        $this->assertFalse($regularUser->isGuest());

        // Guest user should be guest
        $guestUser = User::factory()->guest()->create();
        $this->assertTrue($guestUser->isGuest());
    }

    /**
     * @test
     */
    public function testIsGuestExpired()
    {
        // Regular user should not be expired
        $regularUser = User::factory()->create(['is_guest' => false]);
        $this->assertFalse($regularUser->isGuestExpired());

        // Valid guest user should not be expired
        $validGuestUser = User::factory()->guest()->create([
            'guest_expires_at' => now()->addDays(7),
        ]);
        $this->assertFalse($validGuestUser->isGuestExpired());

        // Expired guest user should be expired
        $expiredGuestUser = User::factory()->expiredGuest()->create();
        $this->assertTrue($expiredGuestUser->isGuestExpired());

        // Guest user without expiration date should not be expired
        $guestWithoutExpiration = User::factory()->guest()->create([
            'guest_expires_at' => null,
        ]);
        $this->assertFalse($guestWithoutExpiration->isGuestExpired());
    }

    /**
     * @test
     */
    public function testIsGuestValid()
    {
        // Regular user should not be valid guest
        $regularUser = User::factory()->create(['is_guest' => false]);
        $this->assertFalse($regularUser->isGuestValid());

        // Valid guest user should be valid
        $validGuestUser = User::factory()->guest()->create([
            'guest_expires_at' => now()->addDays(7),
        ]);
        $this->assertTrue($validGuestUser->isGuestValid());

        // Expired guest user should not be valid
        $expiredGuestUser = User::factory()->expiredGuest()->create();
        $this->assertFalse($expiredGuestUser->isGuestValid());

        // Guest user without expiration should be valid
        $guestWithoutExpiration = User::factory()->guest()->create([
            'guest_expires_at' => null,
        ]);
        $this->assertTrue($guestWithoutExpiration->isGuestValid());
    }

    /**
     * @test
     */
    public function testGuestExpirationEdgeCases()
    {
        // Guest expiring exactly now
        $guestExpiringNow = User::factory()->guest()->create([
            'guest_expires_at' => now(),
        ]);
        // Note: This might be flaky due to timing, but isPast() should handle exactly now as past
        $this->assertTrue($guestExpiringNow->isGuestExpired());

        // Guest expiring in one second
        $guestExpiringLater = User::factory()->guest()->create([
            'guest_expires_at' => now()->addSecond(),
        ]);
        $this->assertFalse($guestExpiringLater->isGuestExpired());

        // Guest expired one second ago
        $guestExpiredEarlier = User::factory()->guest()->create([
            'guest_expires_at' => now()->subSecond(),
        ]);
        $this->assertTrue($guestExpiredEarlier->isGuestExpired());
    }

    /**
     * @test
     */
    public function testGuestAuthenticationRelated()
    {
        // Guest users should not have passwords
        $guestUser = User::factory()->guest()->create();
        $this->assertNull($guestUser->password);

        // Guest users should not be email verified
        $this->assertNull($guestUser->email_verified_at);

        // Valid guest should satisfy guest conditions
        $this->assertTrue($guestUser->isGuest());
        $this->assertTrue($guestUser->isGuestValid());
    }

    /**
     * @test
     */
    public function testGuestToRegularUserConversion()
    {
        // Start with a guest user
        $guestUser = User::factory()->guest()->create();
        $this->assertTrue($guestUser->isGuest());

        // Convert to regular user
        $guestUser->update([
            'is_guest' => false,
            'guest_expires_at' => null,
            'password' => 'new-password',
            'email_verified_at' => now(),
        ]);

        $guestUser = $guestUser->fresh();
        $this->assertFalse($guestUser->isGuest());
        $this->assertFalse($guestUser->isGuestExpired());
        $this->assertFalse($guestUser->isGuestValid());
    }

    // ============================================
    // TODO-010: User 管理者機能テスト
    // ============================================

    /**
     * @test
     */
    public function testIsAdmin()
    {
        // Regular user should not be admin
        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->assertFalse($regularUser->isAdmin());

        // Admin user should be admin
        $adminUser = User::factory()->admin()->create();
        $this->assertTrue($adminUser->isAdmin());
    }

    /**
     * @test
     */
    public function testAdminScope()
    {
        // Create regular users and admin users
        $regularUsers = User::factory(3)->create(['is_admin' => false, 'is_guest' => false]);
        $adminUsers = User::factory(2)->admin()->create();

        // Test that we can query admin users only
        $admins = User::where('is_admin', true)->get();
        $this->assertEquals(2, $admins->count());

        foreach ($admins as $admin) {
            $this->assertTrue($admin->isAdmin());
        }

        // Test that we can query non-admin users only
        $nonAdmins = User::where('is_admin', false)->get();
        $this->assertEquals(3, $nonAdmins->count());

        foreach ($nonAdmins as $user) {
            $this->assertFalse($user->isAdmin());
        }
    }

    /**
     * @test
     */
    public function testAdminSoftDeletes()
    {
        $adminUser = User::factory()->admin()->create();
        $adminId = $adminUser->id;

        // Admin should be deletable with soft delete
        $adminUser->delete();

        // Should still exist in database but be soft deleted
        $this->assertDatabaseHas('users', ['id' => $adminId]);
        $this->assertNotNull($adminUser->deleted_at);

        // Should not be found in normal queries
        $this->assertNull(User::find($adminId));

        // Should be found in trashed queries
        $trashedAdmin = User::withTrashed()->find($adminId);
        $this->assertNotNull($trashedAdmin);
        $this->assertTrue($trashedAdmin->isAdmin());
    }

    /**
     * @test
     */
    public function testFactoryStates()
    {
        // Test admin factory state
        $adminUser = User::factory()->admin()->create();
        $this->assertTrue($adminUser->isAdmin());
        $this->assertNotNull($adminUser->email_verified_at);

        // Test guest factory state
        $guestUser = User::factory()->guest()->create();
        $this->assertTrue($guestUser->isGuest());
        $this->assertNull($guestUser->password);
        $this->assertNull($guestUser->email_verified_at);
        $this->assertNotNull($guestUser->guest_expires_at);

        // Test expired guest factory state
        $expiredGuest = User::factory()->expiredGuest()->create();
        $this->assertTrue($expiredGuest->isGuest());
        $this->assertTrue($expiredGuest->isGuestExpired());

        // Test verified factory state
        $verifiedUser = User::factory()->verified()->create();
        $this->assertNotNull($verifiedUser->email_verified_at);
    }

    /**
     * @test
     */
    public function testAdminAndGuestCombination()
    {
        // Admin cannot be guest (this would be a business logic constraint)
        $adminUser = User::factory()->admin()->create();
        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($adminUser->isGuest());

        // Guest cannot be admin
        $guestUser = User::factory()->guest()->create();
        $this->assertTrue($guestUser->isGuest());
        $this->assertFalse($guestUser->isAdmin());
    }

    /**
     * @test
     */
    public function testUserFactoryWithDebatesIntegration()
    {
        // Test that admin users can participate in debates
        $adminUser = User::factory()->admin()->withDebates(2)->create();
        $this->assertTrue($adminUser->isAdmin());
        $this->assertEquals(2, $adminUser->fresh()->debates_count);

        // Test that guest users can participate in debates
        $guestUser = User::factory()->guest()->create();
        $opponent = User::factory()->create();

        $room = Room::factory()->create();
        Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $guestUser->id,
            'negative_user_id' => $opponent->id,
        ]);

        $this->assertTrue($guestUser->isGuest());
        $this->assertEquals(1, $guestUser->fresh()->debates_count);
    }
}
