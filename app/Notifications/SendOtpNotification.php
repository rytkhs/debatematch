<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * 新しい通知インスタンスを作成
     */
    public function __construct(
        private readonly string $otp
    ) {
        // ブロッキングを防ぐためメール通知をキューに設定
        $this->onQueue('emails');
    }

    /**
     * 通知の配信チャンネルを取得
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * 通知のメール表現を取得
     */
    public function toMail(object $notifiable): MailMessage
    {
        // セキュリティ表示用にマスクされたメールアドレスを取得
        $maskedEmail = $this->getMaskedEmail($notifiable->email);
        
        return (new MailMessage)
            ->subject(__('auth.otp_verification_subject'))
            ->view('emails.otp-verification', [
                'greeting' => __('auth.otp_verification_greeting'),
                'introLines' => [
                    __('auth.otp_verification_message'),
                    __('auth.otp_sent_to_email', ['email' => $maskedEmail]),
                ],
                'otpCode' => $this->otp,
                'outroLines' => [
                    __('auth.otp_expiry_message'),
                    __('auth.otp_security_reminder'),
                ],
                'salutation' => '',
            ])
            // メール転送問題を防ぐためのセキュリティヘッダー
            ->priority(1) // 時間に敏感なOTPのため高優先度
            ->metadata('otp_notification', true);
    }

    /**
     * 通知の配列表現を取得
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'otp_verification',
            'email' => $notifiable->email,
            'sent_at' => now()->toISOString(),
        ];
    }

    /**
     * セキュリティ表示用にメールアドレスをマスク
     */
    private function getMaskedEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $username = $parts[0];
        $domain = $parts[1];

        // ユーザー名をマスク：長さが3より大きい場合は最初の2文字と最後の1文字を表示
        if (strlen($username) > 3) {
            $maskedUsername = substr($username, 0, 2) . str_repeat('*', strlen($username) - 3) . substr($username, -1);
        } else {
            $maskedUsername = substr($username, 0, 1) . str_repeat('*', strlen($username) - 1);
        }

        return $maskedUsername . '@' . $domain;
    }
}