<?php

namespace Tests\Unit\Services;

use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    public function test_generate_creates_six_digit_otp(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->assertIsString($otp);
        $this->assertEquals(6, strlen($otp));
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $otp);
    }

    public function test_generate_creates_different_otps(): void
    {
        $email = 'test@example.com';
        $otp1 = $this->otpService->generate($email);
        $otp2 = $this->otpService->generate($email);
        
        // While theoretically possible to be the same, it's extremely unlikely
        $this->assertNotEquals($otp1, $otp2);
    }

    public function test_store_and_verify_otp(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->otpService->store($email, $otp);
        
        $this->assertTrue($this->otpService->verify($email, $otp));
    }

    public function test_verify_invalid_otp_returns_false(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->otpService->store($email, $otp);
        
        $this->assertFalse($this->otpService->verify($email, '000000'));
    }

    public function test_verify_nonexistent_otp_returns_false(): void
    {
        $email = 'test@example.com';
        
        $this->assertFalse($this->otpService->verify($email, '123456'));
    }

    public function test_otp_invalidated_after_successful_verification(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->otpService->store($email, $otp);
        
        // First verification should succeed
        $this->assertTrue($this->otpService->verify($email, $otp));
        
        // Second verification should fail (OTP invalidated)
        $this->assertFalse($this->otpService->verify($email, $otp));
    }

    public function test_invalidate_removes_otp(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->otpService->store($email, $otp);
        $this->assertTrue($this->otpService->exists($email));
        
        $this->otpService->invalidate($email);
        $this->assertFalse($this->otpService->exists($email));
    }

    public function test_rate_limiting_functionality(): void
    {
        $email = 'test@example.com';
        
        // Initially not rate limited
        $this->assertFalse($this->otpService->isRateLimited($email));
        
        // Increment rate limit 3 times (max allowed)
        for ($i = 0; $i < 3; $i++) {
            $this->otpService->incrementRateLimit($email);
        }
        
        // Should now be rate limited
        $this->assertTrue($this->otpService->isRateLimited($email));
    }

    public function test_failure_count_tracking(): void
    {
        $email = 'test@example.com';
        
        // Initially no failures
        $this->assertEquals(0, $this->otpService->getFailureCount($email));
        
        // Increment failure count
        $count = $this->otpService->incrementFailureCount($email);
        $this->assertEquals(1, $count);
        $this->assertEquals(1, $this->otpService->getFailureCount($email));
        
        // Increment again
        $count = $this->otpService->incrementFailureCount($email);
        $this->assertEquals(2, $count);
    }

    public function test_otp_invalidated_after_max_failures(): void
    {
        $email = 'test@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->otpService->store($email, $otp);
        $this->assertTrue($this->otpService->exists($email));
        
        // Increment failure count to maximum (5)
        for ($i = 0; $i < 5; $i++) {
            $this->otpService->incrementFailureCount($email);
        }
        
        // OTP should be invalidated
        $this->assertFalse($this->otpService->exists($email));
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

    public function test_rate_limit_remaining_time(): void
    {
        $email = 'test@example.com';
        
        // Initially no rate limit
        $this->assertEquals(0, $this->otpService->getRateLimitRemainingTime($email));
        
        // Set rate limit
        $this->otpService->incrementRateLimit($email);
        
        // Should have remaining time (approximately 900 seconds, allowing for small variance)
        $remainingTime = $this->otpService->getRateLimitRemainingTime($email);
        $this->assertGreaterThan(890, $remainingTime);
        $this->assertLessThanOrEqual(900, $remainingTime);
    }

    public function test_timing_safe_comparison(): void
    {
        $email = 'test@example.com';
        $correctOtp = '123456';
        $wrongOtp = '654321';
        
        $this->otpService->store($email, $correctOtp);
        
        // Measure time for correct OTP
        $start = microtime(true);
        $result1 = $this->otpService->verify($email, $correctOtp);
        $time1 = microtime(true) - $start;
        
        // Store again for second test
        $this->otpService->store($email, $correctOtp);
        
        // Measure time for incorrect OTP
        $start = microtime(true);
        $result2 = $this->otpService->verify($email, $wrongOtp);
        $time2 = microtime(true) - $start;
        
        $this->assertTrue($result1);
        $this->assertFalse($result2);
        
        // Times should be relatively similar (timing-safe comparison)
        // Allow for reasonable variance in execution time
        $timeDifference = abs($time1 - $time2);
        $this->assertLessThan(0.001, $timeDifference, 'Timing difference suggests non-constant time comparison');
    }
}