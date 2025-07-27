<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOtpNotification extends Notification
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
            ->line(__('auth.otp_verification_message'))
            ->line('')
            ->line(__('auth.otp_code_display', ['code' => $this->otp]))
            ->line('')
            ->line(__('auth.otp_expiry_message'))
            ->line(__('auth.otp_security_reminder'));
    }

    /**
     * テスト用にOTPコードを取得
     */
    public function getOtpCode(): string
    {
        return $this->otp;
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
