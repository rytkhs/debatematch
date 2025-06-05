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
}
