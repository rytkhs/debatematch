<?php

namespace App\Exceptions;

class OtpExpiredException extends OtpException
{
    public function __construct(string $message = 'OTP has expired', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}