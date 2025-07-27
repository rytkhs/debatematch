<?php

namespace App\Exceptions;

use Exception;

/**
 * OTP関連エラーのベース例外クラス
 */
class OtpException extends Exception
{
    /**
     * 新しいOTP例外インスタンスを作成
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = 'OTP operation failed', int $code = 0, ?\Throwable $previous = null)
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
        return __('auth.otp_error');
    }

    /**
     * エラーハンドリング用の追加コンテキストを取得
     *
     * @return array
     */
    public function getContext(): array
    {
        return [
            'error_type' => 'otp_error',
            'timestamp' => now()->toISOString(),
        ];
    }
}