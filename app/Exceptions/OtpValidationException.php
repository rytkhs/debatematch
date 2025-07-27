<?php

namespace App\Exceptions;

/**
 * OTP検証が失敗した場合にスローされる例外
 */
class OtpValidationException extends OtpException
{
    protected int $failureCount;

    /**
     * 新しいOTP検証例外インスタンスを作成
     *
     * @param int $failureCount 現在の失敗回数
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(int $failureCount = 1, string $message = 'OTP validation failed', int $code = 422, ?\Throwable $previous = null)
    {
        $this->failureCount = $failureCount;
        parent::__construct($message, $code, $previous);
    }

    /**
     * 現在の失敗回数を取得
     *
     * @return int
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    /**
     * 最大失敗回数に達したかチェック
     *
     * @return bool
     */
    public function hasReachedMaxFailures(): bool
    {
        return $this->failureCount >= 5;
    }

    /**
     * OTP無効化前の残り試行回数を取得
     *
     * @return int
     */
    public function getRemainingAttempts(): int
    {
        return max(0, 5 - $this->failureCount);
    }

    /**
     * ローカライズされたエラーメッセージを取得
     *
     * @return string
     */
    public function getLocalizedMessage(): string
    {
        if ($this->hasReachedMaxFailures()) {
            return __('auth.otp_max_failures_reached');
        }

        return __('auth.otp_invalid');
    }

    /**
     * エラーハンドリング用の追加コンテキストを取得
     *
     * @return array
     */
    public function getContext(): array
    {
        return array_merge(parent::getContext(), [
            'error_type' => 'otp_validation_failed',
            'failure_count' => $this->failureCount,
            'remaining_attempts' => $this->getRemainingAttempts(),
            'max_failures_reached' => $this->hasReachedMaxFailures(),
            'user_message' => $this->getLocalizedMessage(),
        ]);
    }

    /**
     * 失敗回数過多によりOTPを無効化すべきかチェック
     *
     * @return bool
     */
    public function shouldInvalidateOtp(): bool
    {
        return $this->hasReachedMaxFailures();
    }
}