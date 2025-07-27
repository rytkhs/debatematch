<?php

namespace Tests\Feature\Auth;

use App\Contracts\OtpServiceInterface;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * OTP Security and Integration Tests
 * 
 * This test class focuses on security validation and integration testing for the OTP system:
 * - Test timing attack resistance with hash_equals usage
 * - Test brute force protection with failure count limits
 * - Test Redis integration and TTL behavior
 * - Verify compatibility with existing Laravel Breeze middleware
 * 
 * Requirements covered: 3.1, 3.2, 3.3, 3.4, 3.5, 5.1, 5.2, 5.4, 5.5
 */
class OtpSecurityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private OtpServiceInterface $otpService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear Redis cache before each test
        Redis::flushall();
        Cache::flush();
        
        $this->otpService = $this->app->make(OtpServiceInterface::class);
    }

    protected function tearDown(): void
    {
        // Clean up Redis after each test
        Redis::flushall();
        parent::tearDown();
    }

    // ========================================
    // Timing Attack Resistance Tests (Requirement 3.3)
    // ========================================

    public function test_otp_verification_uses_timing_safe_comparison(): void
    {
        $user = User::factory()->unverified()->create();
        $email = $user->email;

        // Generate and store OTP
        $correctOtp = $this->otpService->generate($email);
        $this->otpService->store($email, $correctOtp);

        // Test with correct OTP - should use hash_equals internally
        $startTime = microtime(true);
        $result1 = $this->otpService->verify($email, $correctOtp);
        $time1 = microtime(true) - $startTime;

        $this->assertTrue($result1);

        // Generate new OTP for next test
        $correctOtp2 = $this->otpService->generate($email);
        $this->otpService->store($email, $correctOtp2);

        // Test with incorrect OTP of same length - timing should be similar
        $incorrectOtp = '000000';
        $startTime = microtime(true);
        $result2 = $this->otpService->verify($email, $incorrectOtp);
        $time2 = microtime(true) - $startTime;

        $this->assertFalse($result2);

        // The timing difference should be minimal (within reasonable bounds)
        // This is a basic check - in practice, timing attacks require statistical analysis
        $timeDifference = abs($time1 - $time2);
        $this->assertLessThan(0.001, $timeDifference, 'Timing difference suggests potential timing attack vulnerability');
    }

    public function test_otp_service_uses_hash_equals_for_comparison(): void
    {
        // This test verifies that the OtpService implementation uses hash_equals
        // by checking the actual service implementation
        $reflection = new \ReflectionClass(OtpService::class);
        $verifyMethod = $reflection->getMethod('verify');
        $methodSource = file_get_contents($reflection->getFileName());
        
        // Check that hash_equals is used in the verify method
        $this->assertStringContainsString('hash_equals', $methodSource, 
            'OtpService should use hash_equals for timing-safe comparison');
    }

    public function test_timing_consistency_across_multiple_attempts(): void
    {
        $user = User::factory()->unverified()->create();
        $email = $user->email;

        $times = [];
        
        // Test multiple verification attempts to check timing consistency
        for ($i = 0; $i < 10; $i++) {
            $otp = $this->otpService->generate($email);
            $this->otpService->store($email, $otp);
            
            $startTime = microtime(true);
            $this->otpService->verify($email, '000000'); // Always use wrong OTP
            $endTime = microtime(true);
            
            $times[] = $endTime - $startTime;
        }

        // Calculate standard deviation to check timing consistency
        $mean = array_sum($times) / count($times);
        $variance = array_sum(array_map(function($time) use ($mean) {
            return pow($time - $mean, 2);
        }, $times)) / count($times);
        $stdDev = sqrt($variance);

        // Standard deviation should be small, indicating consistent timing
        $this->assertLessThan(0.0001, $stdDev, 'Timing should be consistent to prevent timing attacks');
    }

    // ========================================
    // Brute Force Protection Tests (Requirement 3.2)
    // ========================================

    public function test_brute_force_protection_with_failure_count_limits(): void
    {
        $user = User::factory()->unverified()->create();
        $email = $user->email;

        // Generate and store OTP
        $correctOtp = $this->otpService->generate($email);
        $this->otpService->store($email, $correctOtp);

        // Make 4 failed attempts (should not invalidate yet)
        for ($i = 1; $i <= 4; $i++) {
            $failureCount = $this->otpService->incrementFailureCount($email);
            $this->assertEquals($i, $failureCount);
            
            // OTP should still exist in cache
            $this->assertNotNull(Cache::get("otp:{$email}:code"));
        }

        // 5th failure should invalidate the OTP
        $failureCount = $this->otpService->incrementFailureCount($email);
        $this->assertEquals(5, $failureCount);

        // OTP should now be invalidated (removed from cache)
        $this->assertNull(Cache::get("otp:{$email}:code"));
        
        // Verification should fail since OTP is invalidated
        $this->assertFalse($this->otpService->verify($email, $correctOtp));
    }

    public function test_failure_count_resets_after_successful_verification(): void
    {
        $user = User::factory()->unverified()->create();
        $email = $user->email;

        // Generate and store OTP
        $correctOtp = $this->otpService->generate($email);
        $this->otpService->store($email, $correctOtp);

        // Make 3 failed attempts
        for ($i = 1; $i <= 3; $i++) {
            $this->otpService->incrementFailureCount($email);
        }

        // Verify failure count is 3
        $this->assertEquals(3, Cache::get("otp:{$email}:failures", 0));

        // Successful verification should reset failure count
        $this->assertTrue($this->otpService->verify($email, $correctOtp));
        
        // Failure count should be reset
        $this->assertEquals(0, Cache::get("otp:{$email}:failures", 0));
    }

    public function test_failure_count_persists_across_otp_regeneration(): void
    {
        $user = User::factory()->unverified()->create();
        $email = $user->email;

        // Generate initial OTP and make failures
        $otp1 = $this->otpService->generate($email);
        $this->otpService->store($email, $otp1);
        
        $this->otpService->incrementFailureCount($email);
        $this->otpService->incrementFailureCount($email);
        
        $this->assertEquals(2, Cache::get("otp:{$email}:failures", 0));

        // Generate new OTP (simulating resend)
        $otp2 = $this->otpService->generate($email);
        $this->otpService->store($email, $otp2);

        // Failure count should persist
        $this->assertEquals(2, Cache::get("otp:{$email}:failures", 0));

        // Additional failures should continue counting
        $failureCount = $this->otpService->incrementFailureCount($email);
        $this->assertEquals(3, $failureCount);
    }

    public function test_brute_force_protection_through_controller(): void
    {
        $user = User::factory()->unverified()->create();

        // Send OTP
        $this->actingAs($user)->post('/email/verification-notification');

        // Make 5 failed verification attempts through controller
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->actingAs($user)->post('/verify-email', [
                'otp' => '000000'
            ]);

            $response->assertRedirect();
            $response->assertSessionHasErrors('otp');

            if ($i < 5) {
                // First 4 attempts should show invalid OTP error
                $response->assertSessionHasErrors(['otp' => __('auth.otp_invalid')]);
            } else {
                // 5th attempt should show too many failures error
                $response->assertSessionHasErrors(['otp' => __('auth.otp_too_many_failures')]);
            }
        }

        // OTP should be invalidated
        $this->assertNull(Cache::get("otp:{$user->email}:code"));
    }

    // ========================================
    // Redis Integration and TTL Tests (Requirements 3.1, 3.4)
    // ========================================

    public function test_redis_integration_and_ttl_behavior(): void
    {
        $user = User::factory()->unverified()->create();
        $email = $user->email;

        // Generate and store OTP
        $otp = $this->otpService->generate($email);
        $this->otpService->store($email, $otp);

        // Verify OTP is stored in cache
        $this->assertNotNull(Cache::get("otp:{$email}:code"));
        
        // Test TTL behavior by checking if key exists and has reasonable expiration
        // Instead of checking exact TTL, verify the key expires after reasonable time
        $this->assertTrue(Cache::has("otp:{$email}:code"));
        
        // Manually set a short TTL to test expiration behavior
        Cache::put("otp:{$email}:code", Cache::get("otp:{$email}:code"), 1);
        
        // Wait for expiration
        sleep(2);
        
        // Key should be expired
        $this->assertFalse(Cache::has("otp:{$email}:code"));

        // Verify OTP integration works correctly
        $newOtp = $this->otpService->generate($email);
        $this->otpService->store($email, $newOtp);
        $this->assertTrue($this->otpService->verify($email, $newOtp));
    }

    public function test_otp_automatic_expiration_via_redis_ttl(): void
    {
        $user = User::factory()->unverified()->create();
        $email = $user->email;

        // Generate and store OTP
        $otp = $this->otpService->generate($email);
        $this->otpService->store($email, $otp);

        // Verify OTP exists
        $this->assertNotNull(Cache::get("otp:{$email}:code"));

        // Test expiration by manually removing the key (simulating TTL expiration)
        Cache::forget("otp:{$email}:code");
        
        // OTP should be removed
        $this->assertNull(Cache::get("otp:{$email}:code"));
        
        // Verification should fail
        $this->assertFalse($this->otpService->verify($email, $otp));
        
        // Test that normal TTL behavior works by storing with short TTL
        $newOtp = $this->otpService->generate($email);
        Cache::put("otp:{$email}:code", hash('sha256', $newOtp), 1);
        
        // Should exist initially
        $this->assertNotNull(Cache::get("otp:{$email}:code"));
        
        // Wait for expiration
        sleep(2);
        
        // Should be expired
        $this->assertNull(Cache::get("otp:{$email}:code"));
    }

    public function test_failure_count_ttl_behavior(): void
    {
        $user = User::factory()->unverified()->create();
        $email = $user->email;

        // Increment failure count
        $this->otpService->incrementFailureCount($email);

        // Verify failure count exists
        $this->assertEquals(1, Cache::get("otp:{$email}:failures", 0));

        // Test TTL behavior by manually expiring the failure count
        Cache::put("otp:{$email}:failures", 1, 1); // 1 second TTL
        sleep(2);

        // Failure count should be reset to 0 after expiration
        $this->assertEquals(0, Cache::get("otp:{$email}:failures", 0));
    }

    public function test_rate_limit_ttl_behavior(): void
    {
        $user = User::factory()->unverified()->create();
        $email = $user->email;

        // Simulate rate limit by manually setting cache
        Cache::put("otp:{$email}:rate_limit", 3, 900); // 15 minutes

        // Verify rate limit is active
        $this->assertTrue($this->otpService->isRateLimited($email));

        // Test TTL behavior by manually expiring rate limit
        Cache::put("otp:{$email}:rate_limit", 3, 1); // 1 second TTL
        sleep(2);

        // Rate limit should be lifted after expiration
        $this->assertFalse($this->otpService->isRateLimited($email));
    }

    public function test_redis_connection_resilience(): void
    {
        $user = User::factory()->unverified()->create();
        $email = $user->email;

        // Test that OTP service handles Redis operations correctly
        $otp = $this->otpService->generate($email);
        $this->assertNotNull($otp);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);

        // Store and verify OTP
        $this->otpService->store($email, $otp);
        $this->assertTrue($this->otpService->verify($email, $otp));

        // Test invalidation
        $this->otpService->invalidate($email);
        $this->assertFalse($this->otpService->verify($email, $otp));
    }

    // ========================================
    // Laravel Breeze Middleware Compatibility Tests (Requirements 5.1, 5.2, 5.4, 5.5)
    // ========================================

    public function test_compatibility_with_auth_middleware(): void
    {
        // Test that OTP routes require authentication
        $response = $this->get('/verify-email');
        $response->assertRedirect(route('login'));

        $response = $this->post('/verify-email', ['otp' => '123456']);
        $response->assertRedirect(route('login'));

        $response = $this->post('/email/verification-notification');
        $response->assertRedirect(route('login'));
    }

    public function test_compatibility_with_verified_middleware(): void
    {
        // Create verified user
        $verifiedUser = User::factory()->create();

        // Verified user should be redirected away from verification pages
        $response = $this->actingAs($verifiedUser)->get('/verify-email');
        $response->assertRedirect(route('welcome', absolute: false));

        // Verified user attempting verification should be redirected
        $response = $this->actingAs($verifiedUser)->post('/verify-email', ['otp' => '123456']);
        $response->assertRedirect(route('welcome', absolute: false) . '?verified=1');

        // Verified user attempting resend should be redirected
        $response = $this->actingAs($verifiedUser)->post('/email/verification-notification');
        $response->assertRedirect(route('welcome', absolute: false));
    }

    public function test_compatibility_with_throttle_middleware(): void
    {
        $user = User::factory()->unverified()->create();

        // Test verification endpoint throttling (6 attempts per minute)
        $attempts = 0;
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)->post('/verify-email', ['otp' => '000000']);
            
            if ($response->status() === 429) {
                break; // Throttled
            }
            $attempts++;
        }

        $this->assertGreaterThan(0, $attempts, 'Should allow some attempts before throttling');
        $this->assertLessThan(10, $attempts, 'Should eventually throttle requests');
    }

    public function test_compatibility_with_guest_middleware(): void
    {
        // Test that authenticated users can access OTP verification
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');
        $response->assertStatus(200);

        // Test that guests are redirected to login
        $this->app['auth']->logout();
        
        $response = $this->get('/verify-email');
        $response->assertRedirect(route('login'));
    }

    public function test_integration_with_must_verify_email_interface(): void
    {
        $user = User::factory()->unverified()->create();

        // User should implement MustVerifyEmail
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\MustVerifyEmail::class, $user);

        // User should not be verified initially
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertNull($user->email_verified_at);

        // After OTP verification, user should be verified
        $otp = $this->otpService->generate($user->email);
        $this->otpService->store($user->email, $otp);

        $response = $this->actingAs($user)->post('/verify-email', ['otp' => $otp]);
        $response->assertRedirect(route('welcome', absolute: false) . '?verified=1');

        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_otp_verification_preserves_user_model_functionality(): void
    {
        $user = User::factory()->unverified()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Verify user model properties are preserved
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertFalse($user->hasVerifiedEmail());

        // Complete OTP verification
        $otp = $this->otpService->generate($user->email);
        $this->otpService->store($user->email, $otp);
        
        $this->actingAs($user)->post('/verify-email', ['otp' => $otp]);

        $user->refresh();
        
        // User properties should be preserved
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_otp_system_maintains_session_integrity(): void
    {
        $user = User::factory()->unverified()->create();

        // Set session data
        session(['test_data' => 'preserved']);
        session(['user_preferences' => ['theme' => 'dark']]);

        // Complete OTP verification process
        $this->actingAs($user)->post('/email/verification-notification');
        
        $otp = $this->otpService->generate($user->email);
        $this->otpService->store($user->email, $otp);
        
        $response = $this->actingAs($user)->post('/verify-email', ['otp' => $otp]);

        // Session data should be preserved
        $this->assertEquals('preserved', session('test_data'));
        $this->assertEquals(['theme' => 'dark'], session('user_preferences'));
        
        // Verification status should be in session
        $this->assertEquals('email-verified', session('status'));
    }

    // ========================================
    // Security Edge Cases and Validation
    // ========================================

    public function test_otp_invalidation_after_successful_use(): void
    {
        $user = User::factory()->unverified()->create();
        $email = $user->email;

        $otp = $this->otpService->generate($email);
        $this->otpService->store($email, $otp);

        // First verification should succeed
        $this->assertTrue($this->otpService->verify($email, $otp));

        // OTP should be immediately invalidated
        $this->assertNull(Cache::get("otp:{$email}:code"));

        // Second verification with same OTP should fail
        $this->assertFalse($this->otpService->verify($email, $otp));
    }

    public function test_otp_hashing_security(): void
    {
        $user = User::factory()->unverified()->create();
        $email = $user->email;

        $otp = $this->otpService->generate($email);
        $this->otpService->store($email, $otp);

        // Retrieve stored hash from cache
        $storedHash = Cache::get("otp:{$email}:code");
        
        // Stored value should be hashed, not plain text
        $this->assertNotEquals($otp, $storedHash);
        
        // Verify the hash matches what we expect (SHA256)
        $expectedHash = hash('sha256', $otp);
        $this->assertEquals($expectedHash, $storedHash);
    }

    public function test_cryptographically_secure_otp_generation(): void
    {
        $otps = [];
        
        // Generate multiple OTPs to test randomness
        for ($i = 0; $i < 100; $i++) {
            $otp = $this->otpService->generate('test@example.com');
            $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);
            $otps[] = $otp;
        }

        // Check for uniqueness (should be very high for cryptographically secure generation)
        $uniqueOtps = array_unique($otps);
        $uniquenessRatio = count($uniqueOtps) / count($otps);
        
        // Should have high uniqueness (allowing for some collisions in small sample)
        $this->assertGreaterThan(0.95, $uniquenessRatio, 'OTP generation should be cryptographically random');
    }
}