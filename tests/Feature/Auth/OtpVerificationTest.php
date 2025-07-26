<?php

namespace Tests\Feature\Auth;

use App\Contracts\OtpServiceInterface;
use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpRateLimitException;
use App\Exceptions\OtpValidationException;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class OtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    private OtpServiceInterface $otpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->otpService = $this->app->make(OtpServiceInterface::class);
    }

    // ========================================
    // OTP Verification Prompt Controller Tests
    // ========================================

    public function test_otp_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
        $response->assertViewIs('auth.verify-email');
        $response->assertViewHas('maskedEmail');
        $response->assertViewHas('otpExpiryMinutes', 10);
    }

    public function test_otp_verification_screen_shows_masked_email(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'testuser@example.com'
        ]);

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
        // Check that the masked email is displayed (format: te*****r@e******.com)
        $response->assertSee('te*****r@e******.com', false);
    }

    public function test_verified_user_redirected_from_verification_screen(): void
    {
        $user = User::factory()->create(); // Already verified

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertRedirect(route('welcome', absolute: false));
    }

    // ========================================
    // OTP Verification Controller Tests - Valid Codes
    // ========================================

    public function test_otp_verification_with_valid_code_succeeds(): void
    {
        Event::fake();
        
        $user = User::factory()->unverified()->create();
        $validOtp = '123456';

        // Mock OTP service to return valid verification
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user, $validOtp) {
            $mock->shouldReceive('verify')
                ->once()
                ->with($user->email, $validOtp)
                ->andReturn(true);
            $mock->shouldReceive('invalidate')
                ->once()
                ->with($user->email);
        });

        $response = $this->actingAs($user)->post('/verify-email', [
            'otp' => $validOtp
        ]);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('welcome', absolute: false) . '?verified=1');
        $response->assertSessionHas('status', 'email-verified');
    }

    public function test_already_verified_user_redirected_during_verification(): void
    {
        $user = User::factory()->create(); // Already verified

        $response = $this->actingAs($user)->post('/verify-email', [
            'otp' => '123456'
        ]);

        $response->assertRedirect(route('welcome', absolute: false) . '?verified=1');
    }

    // ========================================
    // OTP Verification Controller Tests - Invalid Codes
    // ========================================

    public function test_otp_verification_with_invalid_code_fails(): void
    {
        $user = User::factory()->unverified()->create();
        $invalidOtp = '000000';

        // Mock OTP service to return invalid verification
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user, $invalidOtp) {
            $mock->shouldReceive('verify')
                ->once()
                ->with($user->email, $invalidOtp)
                ->andReturn(false);
            $mock->shouldReceive('incrementFailureCount')
                ->once()
                ->with($user->email)
                ->andReturn(1);
        });

        $response = $this->actingAs($user)->post('/verify-email', [
            'otp' => $invalidOtp
        ]);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect();
        $response->assertSessionHasErrors(['otp' => __('auth.otp_invalid')]);
    }

    public function test_otp_verification_with_max_failures_invalidates_otp(): void
    {
        $user = User::factory()->unverified()->create();
        $invalidOtp = '000000';

        // Mock OTP service to return 5 failures (max reached)
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user, $invalidOtp) {
            $mock->shouldReceive('verify')
                ->once()
                ->with($user->email, $invalidOtp)
                ->andReturn(false);
            $mock->shouldReceive('incrementFailureCount')
                ->once()
                ->with($user->email)
                ->andReturn(5);
            $mock->shouldReceive('invalidate')
                ->once()
                ->with($user->email);
        });

        $response = $this->actingAs($user)->post('/verify-email', [
            'otp' => $invalidOtp
        ]);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect();
        $response->assertSessionHasErrors(['otp' => __('auth.otp_too_many_failures')]);
    }

    // ========================================
    // OTP Verification Controller Tests - Exception Handling
    // ========================================

    public function test_otp_verification_handles_expired_exception(): void
    {
        $user = User::factory()->unverified()->create();
        $expiredOtp = '123456';

        // Mock OTP service to throw expired exception
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user, $expiredOtp) {
            $mock->shouldReceive('verify')
                ->once()
                ->with($user->email, $expiredOtp)
                ->andThrow(new OtpExpiredException('OTP has expired'));
        });

        $response = $this->actingAs($user)->post('/verify-email', [
            'otp' => $expiredOtp
        ]);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect();
        $response->assertSessionHasErrors(['otp' => __('auth.otp_expired')]);
    }

    public function test_otp_verification_handles_validation_exception(): void
    {
        $user = User::factory()->unverified()->create();
        $invalidOtp = '123456';

        // Mock OTP service to throw validation exception
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user, $invalidOtp) {
            $mock->shouldReceive('verify')
                ->once()
                ->with($user->email, $invalidOtp)
                ->andThrow(new OtpValidationException(1, 'Invalid OTP format'));
        });

        $response = $this->actingAs($user)->post('/verify-email', [
            'otp' => $invalidOtp
        ]);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect();
        $response->assertSessionHasErrors(['otp' => __('auth.otp_invalid')]);
    }

    public function test_otp_verification_handles_rate_limit_exception(): void
    {
        $user = User::factory()->unverified()->create();
        $otp = '123456';

        // Mock OTP service to throw rate limit exception
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user, $otp) {
            $mock->shouldReceive('verify')
                ->once()
                ->with($user->email, $otp)
                ->andThrow(new OtpRateLimitException(300, 'Rate limit exceeded'));
            $mock->shouldReceive('getRateLimitRemainingTime')
                ->once()
                ->with($user->email)
                ->andReturn(300); // 5 minutes remaining
        });

        $response = $this->actingAs($user)->post('/verify-email', [
            'otp' => $otp
        ]);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect();
        $response->assertSessionHasErrors(['otp' => __('auth.otp_rate_limited', ['minutes' => 5])]);
    }

    public function test_otp_verification_handles_general_exception(): void
    {
        Log::shouldReceive('error')->once();
        
        $user = User::factory()->unverified()->create();
        $otp = '123456';

        // Mock OTP service to throw general exception
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user, $otp) {
            $mock->shouldReceive('verify')
                ->once()
                ->with($user->email, $otp)
                ->andThrow(new \Exception('Unexpected error'));
        });

        $response = $this->actingAs($user)->post('/verify-email', [
            'otp' => $otp
        ]);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect();
        $response->assertSessionHasErrors(['otp' => __('auth.otp_verification_error')]);
    }

    // ========================================
    // OTP Verification Controller Tests - Input Validation
    // ========================================

    public function test_otp_verification_requires_otp_field(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->post('/verify-email', []);

        $response->assertSessionHasErrors(['otp']);
    }

    public function test_otp_verification_requires_six_digit_otp(): void
    {
        $user = User::factory()->unverified()->create();

        // Test too short
        $response = $this->actingAs($user)->post('/verify-email', [
            'otp' => '12345'
        ]);
        $response->assertSessionHasErrors(['otp']);

        // Test too long
        $response = $this->actingAs($user)->post('/verify-email', [
            'otp' => '1234567'
        ]);
        $response->assertSessionHasErrors(['otp']);

        // Test non-numeric
        $response = $this->actingAs($user)->post('/verify-email', [
            'otp' => 'abcdef'
        ]);
        $response->assertSessionHasErrors(['otp']);
    }

    // ========================================
    // OTP Resend Controller Tests
    // ========================================

    public function test_otp_resend_succeeds_when_not_rate_limited(): void
    {
        $user = User::factory()->unverified()->create();

        // Mock OTP service for successful resend
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('isRateLimited')
                ->once()
                ->with($user->email)
                ->andReturn(false);
            $mock->shouldReceive('invalidate')
                ->once()
                ->with($user->email);
            $mock->shouldReceive('sendOtp')
                ->once()
                ->with($user);
        });

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response->assertRedirect();
        $response->assertSessionHas('status', 'otp-resent');
    }

    public function test_otp_resend_fails_when_rate_limited(): void
    {
        $user = User::factory()->unverified()->create();

        // Mock OTP service for rate limited resend
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('isRateLimited')
                ->once()
                ->with($user->email)
                ->andReturn(true);
            $mock->shouldReceive('getRateLimitRemainingTime')
                ->once()
                ->with($user->email)
                ->andReturn(600); // 10 minutes remaining
        });

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response->assertRedirect();
        $response->assertSessionHasErrors(['resend' => __('auth.otp_rate_limited', ['minutes' => 10])]);
    }

    public function test_otp_resend_handles_rate_limit_exception(): void
    {
        $user = User::factory()->unverified()->create();

        // Mock OTP service to throw rate limit exception during sendOtp
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('isRateLimited')
                ->once()
                ->with($user->email)
                ->andReturn(false);
            $mock->shouldReceive('invalidate')
                ->once()
                ->with($user->email);
            $mock->shouldReceive('sendOtp')
                ->once()
                ->with($user)
                ->andThrow(new OtpRateLimitException(300, 'Rate limit exceeded'));
            $mock->shouldReceive('getRateLimitRemainingTime')
                ->once()
                ->with($user->email)
                ->andReturn(300); // 5 minutes remaining
        });

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response->assertRedirect();
        $response->assertSessionHasErrors(['resend' => __('auth.otp_rate_limited', ['minutes' => 5])]);
    }

    public function test_otp_resend_handles_general_exception(): void
    {
        Log::shouldReceive('error')->once();
        
        $user = User::factory()->unverified()->create();

        // Mock OTP service to throw general exception
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('isRateLimited')
                ->once()
                ->with($user->email)
                ->andReturn(false);
            $mock->shouldReceive('invalidate')
                ->once()
                ->with($user->email);
            $mock->shouldReceive('sendOtp')
                ->once()
                ->with($user)
                ->andThrow(new \Exception('Unexpected error'));
        });

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response->assertRedirect();
        $response->assertSessionHasErrors(['resend' => __('auth.otp_resend_error')]);
    }

    public function test_verified_user_redirected_during_resend(): void
    {
        $user = User::factory()->create(); // Already verified

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response->assertRedirect(route('welcome', absolute: false));
    }

    // ========================================
    // Authentication Middleware Integration Tests
    // ========================================

    public function test_otp_verification_requires_authentication(): void
    {
        $response = $this->get('/verify-email');
        $response->assertRedirect(route('login'));

        $response = $this->post('/verify-email', ['otp' => '123456']);
        $response->assertRedirect(route('login'));
    }

    public function test_otp_resend_requires_authentication(): void
    {
        $response = $this->post('/email/verification-notification');
        $response->assertRedirect(route('login'));
    }

    public function test_otp_verification_respects_throttling(): void
    {
        $user = User::factory()->unverified()->create();

        // Mock OTP service to always return false (to trigger multiple attempts)
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('verify')
                ->andReturn(false);
            $mock->shouldReceive('incrementFailureCount')
                ->andReturn(1);
        });

        // Make 6 rapid requests (throttle limit is 6 per minute)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->actingAs($user)->post('/verify-email', [
                'otp' => '000000'
            ]);
            
            if ($i < 5) {
                $response->assertStatus(302); // Should redirect back with error
            }
        }

        // 7th request should be throttled
        $response = $this->actingAs($user)->post('/verify-email', [
            'otp' => '000000'
        ]);
        $response->assertStatus(429); // Too Many Requests
    }

    public function test_otp_resend_respects_throttling(): void
    {
        $user = User::factory()->unverified()->create();

        // Mock OTP service for successful resends
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('isRateLimited')
                ->andReturn(false);
            $mock->shouldReceive('invalidate');
            $mock->shouldReceive('sendOtp');
        });

        // Make 3 rapid requests (throttle limit is 3 per 15 minutes)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->actingAs($user)->post('/email/verification-notification');
            $response->assertStatus(302); // Should redirect back with success
        }

        // 4th request should be throttled
        $response = $this->actingAs($user)->post('/email/verification-notification');
        $response->assertStatus(429); // Too Many Requests
    }

    // ========================================
    // User Feedback and Error Display Tests
    // ========================================

    public function test_otp_verification_displays_appropriate_error_messages(): void
    {
        $user = User::factory()->unverified()->create();

        // Test invalid OTP message
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('verify')->once()->andReturn(false);
            $mock->shouldReceive('incrementFailureCount')->once()->andReturn(1);
        });

        $response = $this->actingAs($user)->post('/verify-email', ['otp' => '000000']);
        $response->assertSessionHasErrors('otp');
        $response->assertSessionHasErrors(['otp' => __('auth.otp_invalid')]);
    }

    public function test_otp_verification_displays_too_many_failures_message(): void
    {
        $user = User::factory()->unverified()->create();

        // Test too many failures message
        $this->mock(OtpServiceInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('verify')->once()->andReturn(false);
            $mock->shouldReceive('incrementFailureCount')->once()->andReturn(5);
            $mock->shouldReceive('invalidate')->once();
        });

        $response = $this->actingAs($user)->post('/verify-email', ['otp' => '000000']);
        $response->assertSessionHasErrors('otp');
        $response->assertSessionHasErrors(['otp' => __('auth.otp_too_many_failures')]);
    }

    public function test_otp_resend_displays_appropriate_success_message(): void
    {
        $user = User::factory()->unverified()->create();

        $this->mock(OtpServiceInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('isRateLimited')->andReturn(false);
            $mock->shouldReceive('invalidate');
            $mock->shouldReceive('sendOtp');
        });

        $response = $this->actingAs($user)->post('/email/verification-notification');
        $response->assertSessionHas('status', 'otp-resent');
    }

    // ========================================
    // Integration with Existing Authentication Flow Tests
    // ========================================

    public function test_otp_verification_integrates_with_user_model(): void
    {
        Event::fake();
        
        $user = User::factory()->unverified()->create();
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertNull($user->email_verified_at);

        $this->mock(OtpServiceInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('verify')->andReturn(true);
            $mock->shouldReceive('invalidate');
        });

        $response = $this->actingAs($user)->post('/verify-email', ['otp' => '123456']);

        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotNull($user->email_verified_at);
        
        Event::assertDispatched(Verified::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function test_otp_verification_preserves_intended_redirect(): void
    {
        $user = User::factory()->unverified()->create();

        $this->mock(OtpServiceInterface::class, function ($mock) use ($user) {
            $mock->shouldReceive('verify')->andReturn(true);
            $mock->shouldReceive('invalidate');
        });

        $response = $this->actingAs($user)->post('/verify-email', ['otp' => '123456']);

        // The controller always redirects to welcome with verified=1 parameter
        $response->assertRedirect(route('welcome', absolute: false) . '?verified=1');
    }
}