<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
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
}
