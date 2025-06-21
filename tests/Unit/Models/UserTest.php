<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use App\Models\Room;
use App\Models\RoomUser;
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

    #[Test]
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

    #[Test]
    public function testHiddenAttributes()
    {
        $expectedHidden = [
            'password',
            'remember_token',
        ];

        $this->assertModelBasics(null, $expectedHidden);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function testPasswordHashing()
    {
        $plainPassword = 'test-password';
        $user = User::factory()->create(['password' => $plainPassword]);

        // Password should be hashed
        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }

    #[Test]
    public function testFactoryCreation()
    {
        $this->assertFactoryCreation();
    }

    #[Test]
    public function testSoftDeletes()
    {
        $this->assertSoftDeletes();
    }

    // ============================================
    // TODO-007: User リレーションシップテスト
    // ============================================

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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



    // ============================================
    // TODO-009: User ゲスト機能テスト
    // ============================================

    #[Test]
    public function testIsGuest()
    {
        // Regular user should not be guest
        $regularUser = User::factory()->create(['is_guest' => false]);
        $this->assertFalse($regularUser->isGuest());

        // Guest user should be guest
        $guestUser = User::factory()->guest()->create();
        $this->assertTrue($guestUser->isGuest());
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function testIsAdmin()
    {
        // Regular user should not be admin
        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->assertFalse($regularUser->isAdmin());

        // Admin user should be admin
        $adminUser = User::factory()->admin()->create();
        $this->assertTrue($adminUser->isAdmin());
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function testUserFactoryWithDebatesIntegration()
    {
        // Test that admin users can participate in debates
        $adminUser = User::factory()->admin()->withDebates(2)->create();
        $this->assertTrue($adminUser->isAdmin());
        // 削除されたAccessor属性のテストを削除
        // $this->assertEquals(2, $adminUser->fresh()->debates_count);

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
        // 削除されたAccessor属性のテストを削除
        // $this->assertEquals(1, $guestUser->fresh()->debates_count);
    }

    #[Test]
    public function user_factory_with_debates_integration()
    {
        $user = User::factory()->withDebates()->create();

        $this->assertInstanceOf(User::class, $user);
        // 削除されたAccessor属性のテストを削除
        // $this->assertGreaterThan(0, $user->debates_count);
    }

    /**
     * Phase1 レビュー: 追加のエッジケースとカバレッジ向上テスト
     */


    #[Test]
    public function user_with_very_long_name()
    {
        $longName = str_repeat('あ', 255); // 255文字の日本語
        $user = User::factory()->create(['name' => $longName]);

        $this->assertEquals($longName, $user->name);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $longName
        ]);
    }

    #[Test]
    public function user_with_special_characters_in_name()
    {
        $specialName = '特殊文字テスト!@#$%^&*()_+-=[]{}|;:,.<>?';
        $user = User::factory()->create(['name' => $specialName]);

        $this->assertEquals($specialName, $user->name);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $specialName
        ]);
    }

    #[Test]
    public function user_email_case_sensitivity()
    {
        $email = 'Test.User@Example.COM';
        $user = User::factory()->create(['email' => $email]);

        $this->assertEquals($email, $user->email);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $email
        ]);
    }

    #[Test]
    public function guest_user_with_null_expires_at()
    {
        $user = User::factory()->create([
            'is_guest' => true,
            'guest_expires_at' => null
        ]);

        $this->assertTrue($user->isGuest());
        $this->assertFalse($user->isGuestExpired());
        $this->assertTrue($user->isGuestValid());
    }

    #[Test]
    public function guest_user_with_future_expiration()
    {
        $user = User::factory()->create([
            'is_guest' => true,
            'guest_expires_at' => now()->addHour()
        ]);

        $this->assertTrue($user->isGuest());
        $this->assertFalse($user->isGuestExpired());
        $this->assertTrue($user->isGuestValid());
    }

    #[Test]
    public function guest_user_with_past_expiration()
    {
        $user = User::factory()->create([
            'is_guest' => true,
            'guest_expires_at' => now()->subHour()
        ]);

        $this->assertTrue($user->isGuest());
        $this->assertTrue($user->isGuestExpired());
        $this->assertFalse($user->isGuestValid());
    }

    #[Test]
    public function regular_user_guest_methods()
    {
        $user = User::factory()->create([
            'is_guest' => false,
            'guest_expires_at' => now()->subHour()
        ]);

        $this->assertFalse($user->isGuest());
        $this->assertFalse($user->isGuestExpired());
        $this->assertFalse($user->isGuestValid());
    }

    #[Test]
    public function admin_user_with_guest_flag()
    {
        $user = User::factory()->create([
            'is_admin' => true,
            'is_guest' => true,
            'guest_expires_at' => now()->addHour()
        ]);

        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->isGuest());
        $this->assertTrue($user->isGuestValid());
    }

    #[Test]
    public function user_casts_verification()
    {
        $user = User::factory()->create([
            'email_verified_at' => '2023-01-01 12:00:00',
            'guest_expires_at' => '2023-12-31 23:59:59'
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->guest_expires_at);
        $this->assertEquals('2023-01-01 12:00:00', $user->email_verified_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2023-12-31 23:59:59', $user->guest_expires_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function user_password_hashing_verification()
    {
        $plainPassword = 'test-password-123';
        $user = User::factory()->create(['password' => $plainPassword]);

        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }

    #[Test]
    public function user_hidden_attributes_in_array()
    {
        $user = User::factory()->create();
        $userArray = $user->toArray();

        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
        $this->assertArrayHasKey('name', $userArray);
        $this->assertArrayHasKey('email', $userArray);
    }

    #[Test]
    public function user_statistics_with_complex_scenarios()
    {
        $user = User::factory()->create();
        $opponent1 = User::factory()->create();
        $opponent2 = User::factory()->create();

        // 勝利ディベート（肯定側）
        $room1 = Room::factory()->create();
        $debate1 = Debate::factory()->create([
            'room_id' => $room1->id,
            'affirmative_user_id' => $user->id,
            'negative_user_id' => $opponent1->id
        ]);
        DebateEvaluation::factory()->affirmativeWins()->create(['debate_id' => $debate1->id]);

        // 敗北ディベート（否定側）
        $room2 = Room::factory()->create();
        $debate2 = Debate::factory()->create([
            'room_id' => $room2->id,
            'affirmative_user_id' => $opponent2->id,
            'negative_user_id' => $user->id
        ]);
        DebateEvaluation::factory()->affirmativeWins()->create(['debate_id' => $debate2->id]);

        // 評価なしディベート
        $room3 = Room::factory()->create();
        $debate3 = Debate::factory()->create([
            'room_id' => $room3->id,
            'affirmative_user_id' => $user->id,
            'negative_user_id' => $opponent1->id
        ]);

        // ユーザーが正しくディベートに関連付けられていることを確認
        $this->assertEquals(2, $user->affirmativeDebates()->count());
        $this->assertEquals(1, $user->negativeDebates()->count());

        // 評価が正しく関連付けられていることを確認
        $userDebatesWithEvaluation = Debate::where(function ($query) use ($user) {
            $query->where('affirmative_user_id', $user->id)
                ->orWhere('negative_user_id', $user->id);
        })->has('evaluations')->count();

        $this->assertEquals(2, $userDebatesWithEvaluation);
    }

    #[Test]
    public function user_performance_with_many_relationships()
    {
        $user = User::factory()->create();
        $rooms = Room::factory()->count(20)->create();

        $startTime = microtime(true);

        foreach ($rooms as $index => $room) {
            $side = $index % 2 === 0 ? RoomUser::SIDE_AFFIRMATIVE : RoomUser::SIDE_NEGATIVE;
            $user->rooms()->attach($room->id, ['side' => $side]);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 20ルームの追加が0.5秒以内に完了することを確認
        $this->assertLessThan(0.5, $executionTime);
        $this->assertCount(20, $user->rooms);
    }
}
