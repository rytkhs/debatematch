<?php

namespace App\Services\OpenRouter;

use App\Models\AiFeatureLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * AIによる論題生成サービス
 */
class TopicGeneratorService
{
    private const DEFAULT_TEMPERATURE = 0.8;
    private const DEFAULT_MAX_TOKENS = 2000;
    private const DEFAULT_TIMEOUT_SECONDS = 60;

    public function __construct(
        private OpenRouterClient $client,
        private TopicGeneratorMessageBuilder $messageBuilder
    ) {}

    /**
     * キーワードから新しい論題を生成
     */
    public function generateTopics(
        ?string $keywords,
        ?string $category,
        ?string $difficulty,
        string $language
    ): array {
        $startTime = microtime(true);
        $requestId = null;
        $result = null;
        $exception = null;

        try {
            // ログエントリ作成（best-effort）
            $requestId = $this->createLogEntry('topic_generate', [
                'keywords' => $keywords,
                'category' => $category,
                'difficulty' => $difficulty,
                'language' => $language,
            ]);

            $payload = $this->messageBuilder->build('generate', [
                'keywords' => $keywords,
                'category' => $category,
                'difficulty' => $difficulty,
                'language' => $language,
            ]);

            $response = $this->callApi($payload, [
                'request_type' => 'topic_generate',
                'keywords' => $keywords,
            ]);

            if (!$response['success']) {
                $result = $response;
                return $response;
            }

            $data = $response['data'];

            // topics配列の検証
            if (!isset($data['topics']) || !is_array($data['topics'])) {
                Log::warning('Topic generation returned invalid format', ['data' => $data]);
                $result = [
                    'success' => false,
                    'error' => 'Invalid response format from AI',
                ];
                return $result;
            }

            $result = [
                'success' => true,
                'topics' => $data['topics'],
            ];
            return $result;
        } catch (Throwable $e) {
            Log::error('Topic generation failed', [
                'exception' => $e->getMessage(),
                'keywords' => $keywords,
            ]);

            $exception = $e;
            $result = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            return $result;
        } finally {
            // ログ更新（best-effort、失敗しても本体処理に影響させない）
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $isSuccess = is_array($result) && (($result['success'] ?? false) === true);

            if ($isSuccess) {
                // 成功
                $this->updateLogSuccess($requestId, [
                    'topics' => $result['topics'] ?? [],
                    'topics_count' => count($result['topics'] ?? []),
                ], $durationMs);
            } else {
                // 失敗
                $errorMessage = $exception?->getMessage();

                if ($errorMessage === null && is_array($result)) {
                    $errorMessage = $result['error'] ?? null;
                }

                $statusCode = is_array($result) ? ($result['status'] ?? null) : null;
                if (!is_int($statusCode)) {
                    $statusCode = null;
                }

                $this->updateLogFailure($requestId, $errorMessage ?? 'Unknown error', $statusCode, $durationMs);
            }
        }
    }

    /**
     * 論題の背景情報を取得
     */
    public function getTopicInfo(string $topic, string $language): array
    {
        $startTime = microtime(true);
        $requestId = null;
        $result = null;
        $exception = null;

        try {
            // ログエントリ作成（best-effort）
            $requestId = $this->createLogEntry('topic_info', [
                'topic' => $topic,
                'language' => $language,
            ]);

            $payload = $this->messageBuilder->build('info', [
                'topic' => $topic,
                'language' => $language,
            ]);

            $response = $this->callApi($payload, [
                'request_type' => 'topic_info',
                'topic' => $topic,
            ]);

            if (!$response['success']) {
                $result = $response;
                return $response;
            }

            $data = $response['data'];

            if (!isset($data['info']) || !is_array($data['info'])) {
                Log::warning('Topic info returned invalid format', ['data' => $data]);
                $result = [
                    'success' => false,
                    'error' => 'Invalid response format from AI',
                ];
                return $result;
            }

            $result = [
                'success' => true,
                'info' => $data['info'],
            ];
            return $result;
        } catch (Throwable $e) {
            Log::error('Topic info retrieval failed', [
                'exception' => $e->getMessage(),
                'topic' => $topic,
            ]);

            $exception = $e;
            $result = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            return $result;
        } finally {
            // ログ更新（best-effort、失敗しても本体処理に影響させない）
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $isSuccess = is_array($result) && (($result['success'] ?? false) === true);

            if ($isSuccess) {
                // 成功
                $this->updateLogSuccess($requestId, [
                    'info' => $result['info'] ?? null,
                ], $durationMs);
            } else {
                // 失敗
                $errorMessage = $exception?->getMessage();

                if ($errorMessage === null && is_array($result)) {
                    $errorMessage = $result['error'] ?? null;
                }

                $statusCode = is_array($result) ? ($result['status'] ?? null) : null;
                if (!is_int($statusCode)) {
                    $statusCode = null;
                }

                $this->updateLogFailure($requestId, $errorMessage ?? 'Unknown error', $statusCode, $durationMs);
            }
        }
    }

    /**
     * OpenRouter APIを呼び出し
     */
    private function callApi(array $payload, array $context = []): array
    {
        $options = [
            'timeout_seconds' => (int) Config::get(
                'services.openrouter.topic_generator_timeout',
                self::DEFAULT_TIMEOUT_SECONDS
            ),
            'max_attempts' => 2,
            'temperature' => (float) Config::get(
                'services.openrouter.topic_generator_temperature',
                self::DEFAULT_TEMPERATURE
            ),
            'max_tokens' => (int) Config::get(
                'services.openrouter.topic_generator_max_tokens',
                self::DEFAULT_MAX_TOKENS
            ),
        ];

        $response = $this->client->chatCompletions($payload, $options, $context);

        if (!$response->successful()) {
            $status = $response->status();
            $body = $response->body();
            $jsonBody = $response->json();

            Log::error('Topic generation API failed', [
                'status' => $status,
                'body' => $body,
                'context' => $context,
            ]);

            $errorMessage = $this->getErrorMessage($status, $jsonBody);

            return [
                'success' => false,
                'error' => $errorMessage,
                'status' => $status,
            ];
        }

        $content = $response->json('choices.0.message.content');

        if (empty($content)) {
            return [
                'success' => false,
                'error' => 'Empty response from AI',
            ];
        }

        // JSONをパース
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse AI response as JSON', [
                'content' => $content,
                'error' => json_last_error_msg(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to parse AI response',
            ];
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * ステータスコードに応じたエラーメッセージを取得
     */
    private function getErrorMessage(int $status, ?array $jsonBody): string
    {
        // OpenRouterからのエラーメッセージを取得
        $providerMessage = $jsonBody['error']['message'] ?? null;

        // 特定のエラーパターンをチェック
        if ($providerMessage) {
            // データポリシーエラー（無料モデルの制限）
            if (str_contains($providerMessage, 'data policy')) {
                return __('topic_catalog.ai.error_model_unavailable');
            }
            // モデル機能エラー
            if (str_contains($providerMessage, 'not enabled') || str_contains($providerMessage, 'not supported')) {
                return __('topic_catalog.ai.error_model_unavailable');
            }
        }

        return match ($status) {
            400 => __('topic_catalog.ai.error_bad_request'),
            401, 403 => __('topic_catalog.ai.error_auth'),
            404 => __('topic_catalog.ai.error_model_unavailable'),
            429 => __('topic_catalog.ai.rate_limit_exceeded'),
            500, 502, 503 => __('topic_catalog.ai.error_server'),
            default => __('topic_catalog.ai.generation_failed'),
        };
    }

    /**
     * ログエントリを作成（insert、status='processing'）
     *
     * @return string|null リクエストID（UUID）、失敗時はnull
     */
    private function createLogEntry(string $featureType, array $parameters): ?string
    {
        try {
            $requestId = (string) Str::uuid();

            AiFeatureLog::create([
                'request_id' => $requestId,
                'feature_type' => $featureType,
                'status' => 'processing',
                'user_id' => auth()->id(),
                'parameters' => $parameters,
                'started_at' => now(),
            ]);

            return $requestId;
        } catch (Throwable $e) {
            // ログ記録失敗は本体機能に影響させない
            Log::warning('Failed to create AI feature log entry', [
                'feature_type' => $featureType,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 成功時にログを更新
     */
    private function updateLogSuccess(?string $requestId, ?array $responseData, int $durationMs): void
    {
        if (!$requestId) {
            return;
        }

        try {
            // `where()->update()` は Eloquent casts を通らないため、JSON列は明示的にエンコードする
            $encodedResponseData = null;
            if ($responseData !== null) {
                $encodedResponseData = json_encode($responseData, JSON_UNESCAPED_UNICODE);
                if ($encodedResponseData === false) {
                    $encodedResponseData = null;
                }
            }

            AiFeatureLog::where('request_id', $requestId)->update([
                'status' => 'success',
                'response_data' => $encodedResponseData,
                'finished_at' => now(),
                'duration_ms' => $durationMs,
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to update AI feature log (success)', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 失敗時にログを更新
     */
    private function updateLogFailure(
        ?string $requestId,
        string $errorMessage,
        ?int $statusCode,
        int $durationMs
    ): void {
        if (!$requestId) {
            return;
        }

        try {
            AiFeatureLog::where('request_id', $requestId)->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
                'status_code' => $statusCode,
                'finished_at' => now(),
                'duration_ms' => $durationMs,
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to update AI feature log (failure)', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
