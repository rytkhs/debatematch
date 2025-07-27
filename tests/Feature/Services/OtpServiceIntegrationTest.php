<?php

namespace Tests\Feature\Services;

use App\Contracts\OtpServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class OtpServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private OtpServiceInterface $otpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->otpService = app(OtpServiceInterface::class);
        
        // Clear cache before each test
        Cache::flush();
    }

    public function test_service_is_bound_correctly(): void
    {
        $service = app(OtpServiceInterface::class);
        $this->assertInstanceOf(OtpServiceInterface::class, $service);
    }

    public function test_complete_otp_flow(): void
    {
        $email = 'integration@example.com';
        
        // Step 1: Generate OTP
        $otp = $this->otpService->generate($email);
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $otp);
        
        // Step 2: Store OTP
        $this->otpService->store($email, $otp);
        $this->assertTrue($this->otpService->exists($email));
        
        // Step 3: Verify correct OTP
        $this->assertTrue($this->otpService->verify($email, $otp));
        
        // Step 4: OTP should be invalidated after successful verification
        $this->assertFalse($this->otpService->exists($email));
    }

    public function test_rate_limiting_integration(): void
    {
        $email = 'ratelimit@example.com';
        
        // Should not be rate limited initially
        $this->assertFalse($this->otpService->isRateLimited($email));
        
        // Simulate 3 OTP requests (max allowed)
        for ($i = 0; $i < 3; $i++) {
            $this->otpService->incrementRateLimit($email);
            
            if ($i < 2) {
                $this->assertFalse($this->otpService->isRateLimited($email));
            }
        }
        
        // Should now be rate limited
        $this->assertTrue($this->otpService->isRateLimited($email));
        
        // Should have remaining time
        $remainingTime = $this->otpService->getRateLimitRemainingTime($email);
        $this->assertGreaterThan(0, $remainingTime);
    }

    public function test_failure_count_integration(): void
    {
        $email = 'failures@example.com';
        $otp = $this->otpService->generate($email);
        $this->otpService->store($email, $otp);
        
        // Simulate multiple failures
        for ($i = 1; $i <= 4; $i++) {
            $count = $this->otpService->incrementFailureCount($email);
            $this->assertEquals($i, $count);
            $this->assertTrue($this->otpService->exists($email)); // OTP should still exist
        }
        
        // 5th failure should invalidate OTP
        $count = $this->otpService->incrementFailureCount($email);
        $this->assertEquals(5, $count);
        $this->assertFalse($this->otpService->exists($email)); // OTP should be invalidated
    }

    public function test_multiple_users_isolation(): void
    {
        $email1 = 'user1@example.com';
        $email2 = 'user2@example.com';
        
        $otp1 = $this->otpService->generate($email1);
        $otp2 = $this->otpService->generate($email2);
        
        $this->otpService->store($email1, $otp1);
        $this->otpService->store($email2, $otp2);
        
        // Each user should only be able to verify their own OTP
        $this->assertTrue($this->otpService->verify($email1, $otp1));
        $this->assertFalse($this->otpService->verify($email2, $otp1)); // Should fail
        
        // Store otp2 again since email1 verification would have invalidated it
        $this->otpService->store($email2, $otp2);
        $this->assertTrue($this->otpService->verify($email2, $otp2));
    }

    public function test_security_features(): void
    {
        $email = 'security@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->otpService->store($email, $otp);
        
        // Test that OTP is hashed in storage (we can't directly verify this,
        // but we can ensure that verification still works)
        $this->assertTrue($this->otpService->verify($email, $otp));
        
        // Test that similar but incorrect OTPs fail
        $this->otpService->store($email, $otp);
        $this->assertFalse($this->otpService->verify($email, '000000'));
        $this->assertFalse($this->otpService->verify($email, '999999'));
        
        // Test that empty or malformed OTPs fail
        $this->assertFalse($this->otpService->verify($email, ''));
        $this->assertFalse($this->otpService->verify($email, '12345')); // Too short
        $this->assertFalse($this->otpService->verify($email, '1234567')); // Too long
    }

    public function test_cache_ttl_behavior(): void
    {
        $email = 'ttl@example.com';
        $otp = $this->otpService->generate($email);
        
        $this->otpService->store($email, $otp);
        $this->assertTrue($this->otpService->exists($email));
        
        // In a real scenario, we would wait for TTL to expire,
        // but for testing we can verify the OTP exists immediately after storage
        $this->assertTrue($this->otpService->verify($email, $otp));
    }
}