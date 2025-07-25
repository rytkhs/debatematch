<?php

namespace App\Exceptions;

class OtpValidationException extends OtpException
{
    public function __construct(string $message = 'OTP validation failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}