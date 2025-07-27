<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OtpEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_notification_can_be_sent_to_user(): void
    {
        // OTP通知がユーザーに送信できることをテスト
        Notification::fake();
        
        $user = User::factory()->create(['email' => 'test@example.com']);
        $otp = '123456';

        $user->notify(new SendOtpNotification($otp));

        Notification::assertSentTo($user, SendOtpNotification::class);
    }

    public function test_otp_notification_includes_localized_content(): void
    {
        // OTP通知にローカライズされたコンテンツが含まれることをテスト
        $user = User::factory()->create(['email' => 'test@example.com']);
        $otp = '123456';

        // 英語ロケールをテスト
        app()->setLocale('en');
        $notification = new SendOtpNotification($otp);
        $mailMessage = $notification->toMail($user);
        
        $this->assertEquals(__('auth.otp_verification_subject'), $mailMessage->subject);
        $this->assertArrayHasKey('greeting', $mailMessage->viewData);
        $this->assertEquals(__('auth.otp_verification_greeting'), $mailMessage->viewData['greeting']);

        // 日本語ロケールをテスト
        app()->setLocale('ja');
        $notification = new SendOtpNotification($otp);
        $mailMessage = $notification->toMail($user);
        
        $this->assertEquals(__('auth.otp_verification_subject'), $mailMessage->subject);
        $this->assertEquals(__('auth.otp_verification_greeting'), $mailMessage->viewData['greeting']);
    }

    public function test_otp_notification_includes_security_warnings(): void
    {
        // OTP通知にセキュリティ警告が含まれることをテスト
        $user = User::factory()->create(['email' => 'test@example.com']);
        $otp = '123456';
        $notification = new SendOtpNotification($otp);

        $mailMessage = $notification->toMail($user);

        $this->assertArrayHasKey('outroLines', $mailMessage->viewData);
        $outroLines = $mailMessage->viewData['outroLines'];
        
        $this->assertContains(__('auth.otp_expiry_message'), $outroLines);
        $this->assertContains(__('auth.otp_security_reminder'), $outroLines);
    }

    public function test_otp_notification_includes_masked_email(): void
    {
        // OTP通知にマスクされたメールアドレスが含まれることをテスト
        $user = User::factory()->create(['email' => 'testuser@example.com']);
        $otp = '123456';
        $notification = new SendOtpNotification($otp);

        $mailMessage = $notification->toMail($user);

        $this->assertArrayHasKey('introLines', $mailMessage->viewData);
        $introLines = $mailMessage->viewData['introLines'];
        
        // 導入文の一つにマスクされたメールアドレスが含まれているかチェック
        $maskedEmailFound = false;
        foreach ($introLines as $line) {
            if (str_contains($line, 'te*****r@example.com')) {
                $maskedEmailFound = true;
                break;
            }
        }
        $this->assertTrue($maskedEmailFound, 'マスクされたメールアドレスが導入文に含まれている必要があります');
    }

    public function test_otp_notification_includes_expiry_information(): void
    {
        // OTP通知に有効期限情報が含まれることをテスト
        $user = User::factory()->create(['email' => 'test@example.com']);
        $otp = '123456';
        $notification = new SendOtpNotification($otp);

        $mailMessage = $notification->toMail($user);

        $this->assertArrayHasKey('outroLines', $mailMessage->viewData);
        $outroLines = $mailMessage->viewData['outroLines'];
        
        $this->assertContains(__('auth.otp_expiry_message'), $outroLines);
    }

    public function test_otp_notification_is_queued_properly(): void
    {
        // OTP通知が適切にキューに入れられることをテスト
        Notification::fake();
        
        $user = User::factory()->create(['email' => 'test@example.com']);
        $otp = '123456';

        $user->notify(new SendOtpNotification($otp));

        Notification::assertSentTo($user, SendOtpNotification::class, function (SendOtpNotification $notification) {
            // 通知が正しいキューに入れられることを確認
            return $notification->queue === 'emails';
        });
    }

    public function test_otp_notification_renders_correctly_in_both_languages(): void
    {
        // OTP通知が両言語で正しくレンダリングされることをテスト
        $user = User::factory()->create(['email' => 'test@example.com']);
        $otp = '123456';
        $notification = new SendOtpNotification($otp);

        // 英語レンダリングをテスト
        app()->setLocale('en');
        $mailMessage = $notification->toMail($user);
        $this->assertEquals('Email Verification Code', $mailMessage->subject);

        // 日本語レンダリングをテスト
        app()->setLocale('ja');
        $mailMessage = $notification->toMail($user);
        $this->assertEquals('メールアドレス認証コード', $mailMessage->subject);
    }

    public function test_otp_notification_contains_otp_code(): void
    {
        // OTP通知にOTPコードが含まれることをテスト
        $user = User::factory()->create(['email' => 'test@example.com']);
        $otp = '123456';
        $notification = new SendOtpNotification($otp);

        $mailMessage = $notification->toMail($user);

        $this->assertArrayHasKey('otpCode', $mailMessage->viewData);
        $this->assertEquals($otp, $mailMessage->viewData['otpCode']);
    }

    public function test_otp_notification_has_correct_priority(): void
    {
        // OTP通知が正しい優先度を持つことをテスト
        $user = User::factory()->create(['email' => 'test@example.com']);
        $otp = '123456';
        $notification = new SendOtpNotification($otp);

        $mailMessage = $notification->toMail($user);

        $this->assertEquals(1, $mailMessage->priority);
    }
}