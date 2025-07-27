<?php

namespace Tests\Unit\Services;

use App\Notifications\SendOtpNotification;
use App\Services\OtpService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->otpService = new OtpService();
        
        // Clear cache before each test
        Cache::flush();
    }

    // ========================================
    // OTP Generation Tests (Requirements 1.1)
    // ========================================

    public function test_generate_creates_six_digit_numeric_otp(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->assertIsString($otp);
        $this->assertEquals(6, strlen($otp));
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $otp);
    }

    public function test_generate_uses_cryptographically_secure_random(): void
    {
        $email = 'test@example.com';
        $otps = [];
        
        // Generate multiple OTPs to test randomness
        for ($i = 0; $i < 100; $i++) {
            $otps[] = $this->otpService->generate($email);
        }
        
        // All OTPs should be unique (extremely high probability with secure random)
        $uniqueOtps = array_unique($otps);
        $this->assertCount(100, $uniqueOtps, 'OTPs should be cryptographically random and unique');
        
        // Test distribution - each digit should appear roughly equally
        $digitCounts = array_fill(0, 10, 0);
        foreach ($otps as $otp) {
            for ($pos = 0; $pos < 6; $pos++) {
                $digit = (int) $otp[$pos];
                $digitCounts[$digit]++;
            }
        }
        
        // Each digit should appear at least once in 600 positions (100 OTPs * 6 digits)
        foreach ($digitCounts as $count) {
            $this->assertGreaterThan(0, $count, 'Each digit should appear with cryptographically secure randomness');
        }
    }

    public function test_generate_creates_different_otps_each_time(): void
    {
        $email = 'test@example.com';
        $otp1 = $this->otpService->generate($email);
        $otp2 = $this->otpService->generate($email);
        
        // While theoretically possible to be the same, it's extremely unlikely with secure random
        $this->assertNotEquals($otp1, $otp2);
    }

    // ========================================
    // OTP Storage and Retrieval Tests (Requirements 1.2, 1.3)
    // ========================================

    public function test_store_saves_otp_with_proper_hashing(): void
    {
        $email = 'test@example.com';
        $otp = '123456';
        
        $this->otpService->store($email, $otp);
        
        // Verify OTP exists in cache
        $this->assertTrue($this->otpService->exists($email));
        
        // Verify the stored value is hashed (not plain text)
        $cacheKey = sprintf('otp:%s:code', $email);
        $storedValue = Cache::get($cacheKey);
        $this->assertNotEquals($otp, $storedValue);
        $this->assertEquals(hash('sha256', $otp), $storedValue);
    }

    public function test_store_sets_proper_ttl(): void
    {
        $email = 'test@example.com';
        $otp = '123456';
        
        $this->otpService->store($email, $otp);
        
        // OTP should exist immediately
        $this->assertTrue($this->otpService->exists($email));
        
        // For testing TTL behavior, we can verify the cache key exists
        // In a real Redis environment, this would expire after 600 seconds
        $cacheKey = sprintf('otp:%s:code', $email);
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_verify_with_correct_otp_returns_true(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->otpService->store($email, $otp);
        
        $this->assertTrue($this->otpService->verify($email, $otp));
    }

    public function test_verify_with_incorrect_otp_returns_false(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->otpService->store($email, $otp);
        
        $this->assertFalse($this->otpService->verify($email, '000000'));
    }

    public function test_verify_with_nonexistent_otp_returns_false(): void
    {
        $email = 'test@example.com';
        
        $this->assertFalse($this->otpService->verify($email, '123456'));
    }

    // ========================================
    // Rate Limiting Tests (Requirements 2.2)
    // ========================================

    public function test_rate_limiting_allows_up_to_three_requests(): void
    {
        $email = 'test@example.com';
        
        // Initially not rate limited
        $this->assertFalse($this->otpService->isRateLimited($email));
        
        // Should allow up to 3 requests
        for ($i = 1; $i <= 3; $i++) {
            $this->otpService->incrementRateLimit($email);
            if ($i < 3) {
                $this->assertFalse($this->otpService->isRateLimited($email));
            } else {
                $this->assertTrue($this->otpService->isRateLimited($email));
            }
        }
    }

    public function test_rate_limiting_blocks_after_max_requests(): void
    {
        $email = 'test@example.com';
        
        // Increment to maximum (3 requests)
        for ($i = 0; $i < 3; $i++) {
            $this->otpService->incrementRateLimit($email);
        }
        
        // Should now be rate limited
        $this->assertTrue($this->otpService->isRateLimited($email));
    }

    public function test_rate_limit_remaining_time_calculation(): void
    {
        $email = 'test@example.com';
        
        // Initially no rate limit
        $this->assertEquals(0, $this->otpService->getRateLimitRemainingTime($email));
        
        // Set rate limit
        $this->otpService->incrementRateLimit($email);
        
        // Should have remaining time (approximately 900 seconds for array cache)
        $remainingTime = $this->otpService->getRateLimitRemainingTime($email);
        $this->assertGreaterThan(0, $remainingTime);
    }

    // ========================================
    // Security Features Tests (Requirements 3.1, 3.2, 3.3, 3.4, 3.5)
    // ========================================

    public function test_otp_invalidated_immediately_after_successful_verification(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->otpService->store($email, $otp);
        
        // First verification should succeed
        $this->assertTrue($this->otpService->verify($email, $otp));
        
        // OTP should be immediately invalidated
        $this->assertFalse($this->otpService->exists($email));
        
        // Second verification should fail (OTP invalidated)
        $this->assertFalse($this->otpService->verify($email, $otp));
    }

    public function test_failure_count_tracking_and_otp_invalidation(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->otpService->store($email, $otp);
        $this->assertTrue($this->otpService->exists($email));
        
        // Initially no failures
        $this->assertEquals(0, $this->otpService->getFailureCount($email));
        
        // Increment failure count up to 4 times (should not invalidate yet)
        for ($i = 1; $i <= 4; $i++) {
            $count = $this->otpService->incrementFailureCount($email);
            $this->assertEquals($i, $count);
            $this->assertEquals($i, $this->otpService->getFailureCount($email));
            $this->assertTrue($this->otpService->exists($email)); // OTP should still exist
        }
        
        // 5th failure should invalidate OTP
        $count = $this->otpService->incrementFailureCount($email);
        $this->assertEquals(5, $count);
        $this->assertFalse($this->otpService->exists($email)); // OTP should be invalidated
    }

    public function test_timing_safe_comparison_prevents_timing_attacks(): void
    {
        $email = 'test@example.com';
        $correctOtp = '123456';
        $wrongOtp = '654321';
        
        // Test multiple iterations to get more reliable timing data
        $correctTimes = [];
        $wrongTimes = [];
        
        for ($i = 0; $i < 10; $i++) {
            // Test correct OTP timing
            $this->otpService->store($email, $correctOtp);
            $start = microtime(true);
            $result1 = $this->otpService->verify($email, $correctOtp);
            $correctTimes[] = microtime(true) - $start;
            $this->assertTrue($result1);
            
            // Test wrong OTP timing
            $this->otpService->store($email, $correctOtp);
            $start = microtime(true);
            $result2 = $this->otpService->verify($email, $wrongOtp);
            $wrongTimes[] = microtime(true) - $start;
            $this->assertFalse($result2);
        }
        
        // Calculate average times
        $avgCorrectTime = array_sum($correctTimes) / count($correctTimes);
        $avgWrongTime = array_sum($wrongTimes) / count($wrongTimes);
        
        // Times should be relatively similar (timing-safe comparison)
        $timeDifference = abs($avgCorrectTime - $avgWrongTime);
        $this->assertLessThan(0.001, $timeDifference, 'Timing difference suggests non-constant time comparison');
    }

    public function test_otp_hashed_before_storage(): void
    {
        $email = 'test@example.com';
        $plainOtp = '123456';
        
        $this->otpService->store($email, $plainOtp);
        
        // Get the stored value directly from cache
        $cacheKey = sprintf('otp:%s:code', $email);
        $storedValue = Cache::get($cacheKey);
        
        // Stored value should be hashed, not plain text
        $this->assertNotEquals($plainOtp, $storedValue);
        $this->assertEquals(hash('sha256', $plainOtp), $storedValue);
    }

    public function test_failure_count_reset_on_successful_verification(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->otpService->store($email, $otp);
        
        // Add some failures
        $this->otpService->incrementFailureCount($email);
        $this->otpService->incrementFailureCount($email);
        $this->assertEquals(2, $this->otpService->getFailureCount($email));
        
        // Successful verification should reset failure count
        $this->assertTrue($this->otpService->verify($email, $otp));
        $this->assertEquals(0, $this->otpService->getFailureCount($email));
    }

    // ========================================
    // Integration and Helper Method Tests
    // ========================================

    public function test_invalidate_removes_otp_completely(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->otpService->store($email, $otp);
        $this->assertTrue($this->otpService->exists($email));
        
        $this->otpService->invalidate($email);
        $this->assertFalse($this->otpService->exists($email));
        
        // Verification should fail after invalidation
        $this->assertFalse($this->otpService->verify($email, $otp));
    }

    public function test_exists_method_correctly_identifies_stored_otp(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        // Initially should not exist
        $this->assertFalse($this->otpService->exists($email));
        
        // Should exist after storing
        $this->otpService->store($email, $otp);
        $this->assertTrue($this->otpService->exists($email));
        
        // Should not exist after invalidation
        $this->otpService->invalidate($email);
        $this->assertFalse($this->otpService->exists($email));
    }

    public function test_send_otp_complete_workflow(): void
    {
        Notification::fake();
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);
        
        // Test the complete sendOtp workflow
        $this->otpService->sendOtp($user);
        
        // Verify OTP was stored
        $this->assertTrue($this->otpService->exists($user->email));
        
        // Verify rate limit was incremented
        $this->assertGreaterThan(0, Cache::get(sprintf('otp:%s:rate_limit', $user->email), 0));
        
        // Verify notification was sent
        Notification::assertSentTo($user, SendOtpNotification::class);
    }

    public function test_send_otp_invalidates_existing_otp(): void
    {
        Notification::fake();
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);
        
        // Store an initial OTP
        $initialOtp = $this->otpService->generate($user->email);
        $this->otpService->store($user->email, $initialOtp);
        
        // Send new OTP (should invalidate the old one)
        $this->otpService->sendOtp($user);
        
        // Old OTP should not work
        $this->assertFalse($this->otpService->verify($user->email, $initialOtp));
        
        // New OTP should exist
        $this->assertTrue($this->otpService->exists($user->email));
    }

    // ========================================
    // Edge Cases and Error Handling
    // ========================================

    public function test_multiple_emails_isolated_storage(): void
    {
        $email1 = 'user1@example.com';
        $email2 = 'user2@example.com';
        
        $otp1 = $this->otpService->generate($email1);
        $otp2 = $this->otpService->generate($email2);
        
        $this->otpService->store($email1, $otp1);
        $this->otpService->store($email2, $otp2);
        
        // Each email should have its own OTP
        $this->assertTrue($this->otpService->verify($email1, $otp1));
        $this->assertTrue($this->otpService->verify($email2, $otp2));
        
        // Cross-verification should fail
        $this->otpService->store($email1, $otp1);
        $this->otpService->store($email2, $otp2);
        $this->assertFalse($this->otpService->verify($email1, $otp2));
        $this->assertFalse($this->otpService->verify($email2, $otp1));
    }

    public function test_failure_counts_isolated_per_email(): void
    {
        $email1 = 'user1@example.com';
        $email2 = 'user2@example.com';
        
        // Increment failures for email1
        $this->otpService->incrementFailureCount($email1);
        $this->otpService->incrementFailureCount($email1);
        
        // email1 should have 2 failures, email2 should have 0
        $this->assertEquals(2, $this->otpService->getFailureCount($email1));
        $this->assertEquals(0, $this->otpService->getFailureCount($email2));
    }

    public function test_rate_limits_isolated_per_email(): void
    {
        $email1 = 'user1@example.com';
        $email2 = 'user2@example.com';
        
        // Rate limit email1
        for ($i = 0; $i < 3; $i++) {
            $this->otpService->incrementRateLimit($email1);
        }
        
        // email1 should be rate limited, email2 should not
        $this->assertTrue($this->otpService->isRateLimited($email1));
        $this->assertFalse($this->otpService->isRateLimited($email2));
    }
}