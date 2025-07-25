<?php

namespace App\Contracts;

interface OtpServiceInterface
{
    /**
     * 指定されたメールアドレスに対して6桁のOTPを生成する
     */
    public function generate(string $email): string;

    /**
     * OTPをTTL付きでキャッシュに保存する
     */
    public function store(string $email, string $otp): void;

    /**
     * 提供されたOTPを保存されたものと照合する
     */
    public function verify(string $email, string $otp): bool;

    /**
     * 指定されたメールアドレスのOTPを無効化する
     */
    public function invalidate(string $email): void;

    /**
     * メールアドレスがOTPリクエストのレート制限対象かチェックする
     */
    public function isRateLimited(string $email): bool;

    /**
     * 失敗回数をインクリメントし、現在の回数を返す
     */
    public function incrementFailureCount(string $email): int;

    /**
     * レート制限が解除されるまでの残り時間を取得する
     */
    public function getRateLimitRemainingTime(string $email): int;

    /**
     * OTPを生成、保存し、メール通知でユーザーに送信する
     */
    public function sendOtp(object $user): void;
}