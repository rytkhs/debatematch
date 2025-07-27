<?php

namespace App\Exceptions;

/**
 * OTPが期限切れの場合にスローされる例外
 */
class OtpExpiredException extends OtpException
{
    /**
     * 新しいOTP期限切れ例外インスタンスを作成
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = 'OTP has expired', int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * ローカライズされたエラーメッセージを取得
     *
     * @return string
     */
    public function getLocalizedMessage(): string
    {
        return __('auth.otp_expired');
    }

    /**
     * エラーハンドリング用の追加コンテキストを取得
     *
     * @return array
     */
    public function getContext(): array
    {
        return array_merge(parent::getContext(), [
            'error_type' => 'otp_expired',
            'action_required' => 'request_new_otp',
            'user_message' => $this->getLocalizedMessage(),
        ]);
    }

    /**
     * 新しいOTPを自動的にリクエストすべきかチェック
     *
     * @return bool
     */
    public function shouldRequestNewOtp(): bool
    {
        return true;
    }
}