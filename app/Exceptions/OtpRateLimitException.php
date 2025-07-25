<?php

namespace App\Exceptions;

class OtpRateLimitException extends OtpException
{
    public function __construct(string $message = 'OTP rate limit exceeded', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}