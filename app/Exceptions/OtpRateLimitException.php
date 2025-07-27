<?php

namespace App\Exceptions;

/**
 * OTPレート制限を超過した場合にスローされる例外
 */
class OtpRateLimitException extends OtpException
{
    protected int $retryAfter;

    /**
     * 新しいOTPレート制限例外インスタンスを作成
     *
     * @param int $retryAfter 次の試行が許可されるまでの秒数
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(int $retryAfter = 900, string $message = 'OTP rate limit exceeded', int $code = 429, ?\Throwable $previous = null)
    {
        $this->retryAfter = $retryAfter;
        parent::__construct($message, $code, $previous);
    }

    /**
     * リトライ可能時間を秒単位で取得
     *
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * リトライ可能時間を分単位で取得
     *
     * @return int
     */
    public function getRetryAfterMinutes(): int
    {
        return (int) ceil($this->retryAfter / 60);
    }

    /**
     * ローカライズされたエラーメッセージを取得
     *
     * @return string
     */
    public function getLocalizedMessage(): string
    {
        return __('auth.otp_rate_limited', ['minutes' => $this->getRetryAfterMinutes()]);
    }

    /**
     * エラーハンドリング用の追加コンテキストを取得
     *
     * @return array
     */
    public function getContext(): array
    {
        return array_merge(parent::getContext(), [
            'error_type' => 'otp_rate_limited',
            'retry_after_seconds' => $this->retryAfter,
            'retry_after_minutes' => $this->getRetryAfterMinutes(),
            'user_message' => $this->getLocalizedMessage(),
        ]);
    }

    /**
     * レート制限用のHTTPヘッダーを取得
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return [
            'Retry-After' => $this->retryAfter,
            'X-RateLimit-Limit' => 3,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => now()->addSeconds($this->retryAfter)->timestamp,
        ];
    }
}