<?php

namespace App\Services;

use App\Contracts\OtpServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OtpService implements OtpServiceInterface
{
    private const OTP_LENGTH = 6;
    private const OTP_TTL = 600; // 10分間（秒）
    private const RATE_LIMIT_TTL = 900; // 15分間（秒）
    private const MAX_REQUESTS_PER_PERIOD = 3;
    private const MAX_FAILURES = 5;
    
    private const CACHE_KEY_OTP = 'otp:%s:code';
    private const CACHE_KEY_FAILURES = 'otp:%s:failures';
    private const CACHE_KEY_RATE_LIMIT = 'otp:%s:rate_limit';

    /**
     * 暗号学的に安全な乱数生成を使用して6桁のOTPを生成
     */
    public function generate(string $email): string
    {
        // 暗号学的に安全なrandom_int()を使用して6桁のOTPを生成
        $otp = '';
        for ($i = 0; $i < self::OTP_LENGTH; $i++) {
            $otp .= random_int(0, 9);
        }
        
        Log::info('OTP generated for email', ['email' => $email]);
        
        return $otp;
    }

    /**
     * 適切なTTLとハッシュ化でOTPをRedisキャッシュに保存
     */
    public function store(string $email, string $otp): void
    {
        $cacheKey = sprintf(self::CACHE_KEY_OTP, $email);
        
        // セキュリティのためOTPを保存前にハッシュ化
        $hashedOtp = hash('sha256', $otp);
        
        // TTL付きでRedisに保存
        Cache::put($cacheKey, $hashedOtp, self::OTP_TTL);
        
        Log::info('OTP stored in cache', ['email' => $email, 'ttl' => self::OTP_TTL]);
    }

    /**
     * タイミングセーフ比較を使用して提供されたOTPを保存されたものと照合
     */
    public function verify(string $email, string $otp): bool
    {
        $cacheKey = sprintf(self::CACHE_KEY_OTP, $email);
        $storedHashedOtp = Cache::get($cacheKey);
        
        if (!$storedHashedOtp) {
            Log::warning('OTP verification failed - no stored OTP found', ['email' => $email]);
            return false;
        }
        
        // 比較のため提供されたOTPをハッシュ化
        $providedHashedOtp = hash('sha256', $otp);
        
        // タイミング攻撃を防ぐためタイミングセーフ比較を使用
        $isValid = hash_equals($storedHashedOtp, $providedHashedOtp);
        
        if ($isValid) {
            // 検証成功後、即座にOTPを無効化
            $this->invalidate($email);
            // 検証成功時に失敗回数をリセット
            $this->resetFailureCount($email);
            Log::info('OTP verification successful', ['email' => $email]);
        } else {
            Log::warning('OTP verification failed - invalid OTP', ['email' => $email]);
        }
        
        return $isValid;
    }

    /**
     * 指定されたメールアドレスのOTPを無効化
     */
    public function invalidate(string $email): void
    {
        $cacheKey = sprintf(self::CACHE_KEY_OTP, $email);
        Cache::forget($cacheKey);
        
        Log::info('OTP invalidated', ['email' => $email]);
    }

    /**
     * メールアドレスがOTPリクエストでレート制限されているかチェック
     */
    public function isRateLimited(string $email): bool
    {
        $cacheKey = sprintf(self::CACHE_KEY_RATE_LIMIT, $email);
        $requestCount = Cache::get($cacheKey, 0);
        
        $isLimited = $requestCount >= self::MAX_REQUESTS_PER_PERIOD;
        
        if ($isLimited) {
            Log::warning('Rate limit exceeded', ['email' => $email, 'count' => $requestCount]);
        }
        
        return $isLimited;
    }

    /**
     * 失敗回数をインクリメントし、現在の回数を返す
     */
    public function incrementFailureCount(string $email): int
    {
        $cacheKey = sprintf(self::CACHE_KEY_FAILURES, $email);
        $currentCount = Cache::get($cacheKey, 0);
        $newCount = $currentCount + 1;
        
        // 失敗回数をOTPと同じTTLで保存
        Cache::put($cacheKey, $newCount, self::OTP_TTL);
        
        // 最大失敗回数に達した場合、現在のOTPを無効化
        if ($newCount >= self::MAX_FAILURES) {
            $this->invalidate($email);
            Log::warning('Max failures reached, OTP invalidated', [
                'email' => $email, 
                'failures' => $newCount
            ]);
        }
        
        Log::info('Failure count incremented', ['email' => $email, 'count' => $newCount]);
        
        return $newCount;
    }

    /**
     * レート制限が期限切れになるまでの残り時間を取得
     */
    public function getRateLimitRemainingTime(string $email): int
    {
        $cacheKey = sprintf(self::CACHE_KEY_RATE_LIMIT, $email);
        
        // Redisストアを使用しているかチェック
        $store = Cache::getStore();
        if (method_exists($store, 'getRedis')) {
            // Redisからレート制限キーの残りTTLを取得
            $remainingSeconds = $store->getRedis()->ttl($cacheKey);
            return max(0, $remainingSeconds);
        }
        
        // 非Redisストア（テストのArrayストアなど）のフォールバック
        if (Cache::has($cacheKey)) {
            // テスト目的で適切なデフォルト値を返す
            return self::RATE_LIMIT_TTL;
        }
        
        return 0;
    }

    /**
     * レート制限カウンターをインクリメント
     */
    public function incrementRateLimit(string $email): void
    {
        $cacheKey = sprintf(self::CACHE_KEY_RATE_LIMIT, $email);
        $currentCount = Cache::get($cacheKey, 0);
        $newCount = $currentCount + 1;
        
        // レート制限TTLで保存
        Cache::put($cacheKey, $newCount, self::RATE_LIMIT_TTL);
        
        Log::info('Rate limit counter incremented', ['email' => $email, 'count' => $newCount]);
    }

    /**
     * 指定されたメールアドレスの失敗回数をリセット
     */
    private function resetFailureCount(string $email): void
    {
        $cacheKey = sprintf(self::CACHE_KEY_FAILURES, $email);
        Cache::forget($cacheKey);
        
        Log::info('Failure count reset', ['email' => $email]);
    }

    /**
     * 指定されたメールアドレスの現在の失敗回数を取得
     */
    public function getFailureCount(string $email): int
    {
        $cacheKey = sprintf(self::CACHE_KEY_FAILURES, $email);
        return Cache::get($cacheKey, 0);
    }

    /**
     * 指定されたメールアドレスのOTPが存在するかチェック
     */
    public function exists(string $email): bool
    {
        $cacheKey = sprintf(self::CACHE_KEY_OTP, $email);
        return Cache::has($cacheKey);
    }
}