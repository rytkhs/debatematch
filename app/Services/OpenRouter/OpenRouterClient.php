<?php

namespace App\Services\OpenRouter;

use App\Services\Traits\HandlesOpenRouterRetry;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OpenRouterClient
{
    use HandlesOpenRouterRetry;

    private const CHAT_COMPLETIONS_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const DEFAULT_TIMEOUT_SECONDS = 240;
    private const DEFAULT_MAX_ATTEMPTS = 3;
    private const DEFAULT_MAX_TOKENS_CAP = 30000;
    private const MIN_TEMPERATURE = 0.0;
    private const MAX_TEMPERATURE = 2.0;

    /**
     * Fields that are safe to merge into the payload from $options.
     * Values of null/false are treated as "omit".
     */
    private const OPTIONAL_PAYLOAD_FIELDS = [
        'session_id',
        'metadata',
        'reasoning',
        'response_format',
        'max_tokens',
        'temperature',
        'top_p',
        'presence_penalty',
        'frequency_penalty',
        'seed',
        'stop',
    ];

    private string $apiKey;
    private string $referer;
    private string $title;
    private int $defaultTimeoutSeconds;

    public function __construct(
        ?string $apiKey = null,
        ?string $referer = null,
        ?string $title = null,
        ?int $defaultTimeoutSeconds = null
    ) {
        $this->apiKey = $apiKey ?? (string) Config::get('services.openrouter.api_key');
        $this->referer = $referer ?? (string) Config::get('services.openrouter.referer', config('app.url'));
        $this->title = $title ?? (string) Config::get('services.openrouter.title', config('app.name'));
        $this->defaultTimeoutSeconds = $defaultTimeoutSeconds
            ?? (int) Config::get('services.openrouter.timeout_seconds', self::DEFAULT_TIMEOUT_SECONDS);
    }

    /**
     * Call OpenRouter /chat/completions.
     *
     * @param array $payload Required: model, messages
     * @param array $options Optional payload fields + request options:
     *                       - timeout_seconds (int)
     *                       - max_attempts (int)
     * @param array $context Log context (e.g. debate_id, turn)
     */
    public function chatCompletions(array $payload, array $options = [], array $context = []): Response
    {
        $this->assertConfigured();

        // payload は message builder 側の責務として固定し、実行時オプションだけを安全に上書きできるようにする。
        $payload = $this->mergeOptionalFields($payload, $options);

        $timeoutSeconds = isset($options['timeout_seconds'])
            ? (int) $options['timeout_seconds']
            : $this->defaultTimeoutSeconds;
        $maxAttempts = isset($options['max_attempts'])
            ? (int) $options['max_attempts']
            : self::DEFAULT_MAX_ATTEMPTS;

        $this->logRequest($payload, $context);

        $retryAttempt = 0;

        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'HTTP-Referer' => $this->referer,
            'X-Title' => $this->title,
            'Content-Type' => 'application/json',
        ])
            ->timeout($timeoutSeconds)
            ->retry(
                $maxAttempts,
                function (int $attempt, Throwable $exception) use (&$retryAttempt, $context, $maxAttempts) {
                    $retryAttempt = $attempt;
                    $delayMs = $this->calculateBackoffDelay($attempt);

                    // 失敗時に後追い調査できるよう、再試行の理由と遅延を必ずログに残す。
                    Log::warning('OpenRouter API retry attempt', array_merge($context, [
                        'attempt' => $attempt,
                        'max_attempts' => $maxAttempts,
                        'error' => $exception->getMessage(),
                        'delay_ms' => $delayMs,
                    ]));

                    return $delayMs;
                },
                function (Throwable $exception, PendingRequest $request) {
                    return $this->shouldRetry($exception);
                },
                throw: false
            )
            ->post(self::CHAT_COMPLETIONS_URL, $payload);
    }

    private function assertConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('OpenRouter API key is not configured.');
        }
    }

    private function mergeOptionalFields(array $payload, array $options): array
    {
        foreach (self::OPTIONAL_PAYLOAD_FIELDS as $field) {
            if (!array_key_exists($field, $options)) {
                continue;
            }
            if (array_key_exists($field, $payload)) {
                // builder 側が明示したフィールドは尊重する（呼び出し側で上書きしない）。
                continue;
            }

            $value = $options[$field];
            if ($value === null || $value === false) {
                // null/false は「送らない」の意味として扱う（OpenRouter へ余計な指定をしない）。
                continue;
            }

            if ($field === 'max_tokens') {
                $value = (int) $value;
                if ($value <= 0) {
                    continue;
                }
                // 異常に大きい max_tokens は課金/遅延の原因になるので、環境設定で上限を掛ける。
                $cap = (int) Config::get('services.openrouter.max_tokens_cap', self::DEFAULT_MAX_TOKENS_CAP);
                if ($cap > 0 && $value > $cap) {
                    $value = $cap;
                }
            }

            if ($field === 'temperature') {
                if (!is_numeric($value)) {
                    continue;
                }
                $value = (float) $value;
                // モデルの許容範囲外はエラーになりうるため、クランプして事故を減らす。
                if ($value < self::MIN_TEMPERATURE) {
                    $value = self::MIN_TEMPERATURE;
                } elseif ($value > self::MAX_TEMPERATURE) {
                    $value = self::MAX_TEMPERATURE;
                }
            }

            $payload[$field] = $value;
        }

        return $payload;
    }

    private function logRequest(array $payload, array $context): void
    {
        $messageCount = null;
        $approxChars = null;

        if (isset($payload['messages']) && is_array($payload['messages'])) {
            $messageCount = count($payload['messages']);
            $approxChars = 0;

            foreach ($payload['messages'] as $message) {
                if (!is_array($message)) {
                    continue;
                }
                $approxChars += mb_strlen((string) ($message['content'] ?? ''));
            }
        }

        Log::debug('OpenRouter request', array_merge($context, [
            'model' => $payload['model'] ?? null,
            'message_count' => $messageCount,
            'approx_chars' => $approxChars,
            'session_id' => $payload['session_id'] ?? null,
            'has_metadata' => array_key_exists('metadata', $payload),
            'has_reasoning' => array_key_exists('reasoning', $payload),
            'has_response_format' => array_key_exists('response_format', $payload),
            'max_tokens' => $payload['max_tokens'] ?? null,
            'temperature' => $payload['temperature'] ?? null,
        ]));
    }
}
