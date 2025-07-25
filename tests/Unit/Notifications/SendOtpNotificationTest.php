<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class SendOtpNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_uses_correct_delivery_channels(): void
    {
        // 通知が正しい配信チャンネルを使用することをテスト
        $notification = new SendOtpNotification('123456');
        $user = User::factory()->create();

        $channels = $notification->via($user);

        $this->assertEquals(['mail'], $channels);
    }

    public function test_notification_creates_mail_message_with_correct_subject(): void
    {
        // 通知が正しい件名でメールメッセージを作成することをテスト
        $otp = '123456';
        $notification = new SendOtpNotification($otp);
        $user = User::factory()->create(['email' => 'test@example.com']);

        $mailMessage = $notification->toMail($user);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals(__('auth.otp_verification_subject'), $mailMessage->subject);
    }

    public function test_notification_uses_custom_view_with_otp_code(): void
    {
        // 通知がOTPコード付きのカスタムビューを使用することをテスト
        $otp = '123456';
        $notification = new SendOtpNotification($otp);
        $user = User::factory()->create(['email' => 'test@example.com']);

        $mailMessage = $notification->toMail($user);

        $this->assertEquals('emails.otp-verification', $mailMessage->view);
        $this->assertArrayHasKey('otpCode', $mailMessage->viewData);
        $this->assertEquals($otp, $mailMessage->viewData['otpCode']);
    }

    public function test_notification_includes_masked_email_in_view_data(): void
    {
        // 通知のビューデータにマスクされたメールアドレスが含まれることをテスト
        $otp = '123456';
        $notification = new SendOtpNotification($otp);
        $user = User::factory()->create(['email' => 'testuser@example.com']);

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

    public function test_notification_includes_security_information(): void
    {
        // 通知にセキュリティ情報が含まれることをテスト
        $otp = '123456';
        $notification = new SendOtpNotification($otp);
        $user = User::factory()->create();

        $mailMessage = $notification->toMail($user);

        $this->assertArrayHasKey('outroLines', $mailMessage->viewData);
        $outroLines = $mailMessage->viewData['outroLines'];
        
        // セキュリティリマインダーが含まれているかチェック
        $this->assertContains(__('auth.otp_security_reminder'), $outroLines);
    }

    public function test_notification_has_high_priority(): void
    {
        // 通知が高優先度を持つことをテスト
        $otp = '123456';
        $notification = new SendOtpNotification($otp);
        $user = User::factory()->create();

        $mailMessage = $notification->toMail($user);

        $this->assertEquals(1, $mailMessage->priority);
    }

    public function test_notification_to_array_returns_correct_data(): void
    {
        // 通知のtoArrayメソッドが正しいデータを返すことをテスト
        $otp = '123456';
        $notification = new SendOtpNotification($otp);
        $user = User::factory()->create(['email' => 'test@example.com']);

        $array = $notification->toArray($user);

        $this->assertEquals('otp_verification', $array['type']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertArrayHasKey('sent_at', $array);
    }

    public function test_email_masking_works_correctly(): void
    {
        // メールマスキングが正しく動作することをテスト
        $notification = new SendOtpNotification('123456');
        
        // プライベートメソッドをテストするためにリフレクションを使用
        $reflection = new \ReflectionClass($notification);
        $method = $reflection->getMethod('getMaskedEmail');
        $method->setAccessible(true);

        // 様々なメール形式をテスト
        $this->assertEquals('te*****r@example.com', $method->invoke($notification, 'testuser@example.com'));
        $this->assertEquals('a**@example.com', $method->invoke($notification, 'abc@example.com'));
        $this->assertEquals('a*@example.com', $method->invoke($notification, 'ab@example.com'));
        $this->assertEquals('jo****e@example.com', $method->invoke($notification, 'johndoe@example.com'));
    }

    public function test_notification_is_queued(): void
    {
        // 通知がキューに入れられることをテスト
        $notification = new SendOtpNotification('123456');
        
        $this->assertContains('Illuminate\Contracts\Queue\ShouldQueue', class_implements($notification));
        $this->assertEquals('emails', $notification->queue);
    }
}