<?php

namespace App\Contracts;

interface OtpServiceInterface
{
    /**
     * Generate a 6-digit OTP for the given email
     */
    public function generate(string $email): string;

    /**
     * Store the OTP in cache with TTL
     */
    public function store(string $email, string $otp): void;

    /**
     * Verify the provided OTP against the stored one
     */
    public function verify(string $email, string $otp): bool;

    /**
     * Invalidate the OTP for the given email
     */
    public function invalidate(string $email): void;

    /**
     * Check if the email is rate limited for OTP requests
     */
    public function isRateLimited(string $email): bool;

    /**
     * Increment failure count and return current count
     */
    public function incrementFailureCount(string $email): int;

    /**
     * Get remaining time until rate limit expires
     */
    public function getRateLimitRemainingTime(string $email): int;
}