<?php

namespace App\Services\Traits;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Throwable;

trait HandlesOpenRouterRetry
{
    /**
     * リトライ対象のエラーかどうかを判定する
     *
     * @param Throwable $exception
     * @return bool
     */
    protected function shouldRetry(Throwable $exception): bool
    {
        // ConnectionException（タイムアウトなど）の場合はリトライ
        if ($exception instanceof ConnectionException) {
            return true;
        }

        // RequestException (4xx, 5xx) の判定
        if ($exception instanceof RequestException) {
            $status = $exception->response->status();
            // 429 Too Many Requests や 5xx Server Error は一時的なエラーの可能性があるためリトライ
            return $status === 429 || ($status >= 500 && $status <= 599);
        }

        $message = $exception->getMessage();

        // cURL error 18: transfer closed with outstanding read data remaining
        if (
            str_contains($message, 'cURL error 18') ||
            str_contains($message, 'transfer closed with outstanding read data remaining')
        ) {
            return true;
        }

        // cURL error 56: OpenSSL SSL_read: unexpected eof while reading
        if (
            str_contains($message, 'cURL error 56') ||
            str_contains($message, 'OpenSSL SSL_read') ||
            str_contains($message, 'unexpected eof while reading')
        ) {
            return true;
        }

        return false;
    }

    /**
     * 指数バックオフの遅延時間を計算する（ミリ秒）
     *
     * @param int $attempt リトライ試行回数（1から開始）
     * @param int $baseDelayMs 基本遅延時間（ミリ秒）
     * @return int 遅延時間（ミリ秒）
     */
    protected function calculateBackoffDelay(int $attempt, int $baseDelayMs = 100): int
    {
        return $baseDelayMs * pow(2, $attempt - 1);
    }
}
