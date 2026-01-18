<?php

namespace App\Services\OpenRouter;

use App\Models\AiFeatureLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * AIによる論題関連機能サービス（生成、分析など）
 */
class TopicAiService
{
    private const DEFAULT_TEMPERATURE = 0.8;
    private const DEFAULT_MAX_TOKENS = 2000;
    private const DEFAULT_TIMEOUT_SECONDS = 60;

    public function __construct(
        private OpenRouterClient $client,
        private TopicAiMessageBuilder $messageBuilder
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
        return $this->executeAiRequest(
            'topic_generate',
            'generate',
            [
                'keywords' => $keywords,
                'category' => $category,
                'difficulty' => $difficulty,
                'language' => $language,
            ],
            fn($data) => [
                'topics' => $data['topics'] ?? [],
            ],
            function($data) {
                if (!isset($data['topics']) || !is_array($data['topics'])) {
                    throw new \RuntimeException('Invalid response format from AI: topics array missing');
                }
            }
        );
    }

    /**
     * 論題の背景情報を取得
     */
    public function getTopicInfo(string $topic, string $language): array
    {
        return $this->executeAiRequest(
            'topic_info',
            'info',
            [
                'topic' => $topic,
                'language' => $language,
            ],
            fn($data) => [
                'info' => $data['info'] ?? null,
            ],
            function($data) {
                if (!isset($data['info']) || !is_array($data['info'])) {
                    throw new \RuntimeException('Invalid response format from AI: info object missing');
                }
            }
        );
    }

    /**
     * 共通AIリクエスト実行処理
     *
     * @param string $featureType ログ用機能タイプ
     * @param string $buildType メッセージビルダー用タイプ
     * @param array $params パラメータ
     * @param callable $successFormatter 成功時のレスポンス整形用コールバック
     * @param callable|null $validator レスポンスデータの追加検証用コールバック
     * @return array
     */
    private function executeAiRequest(
        string $featureType,
        string $buildType,
        array $params,
        callable $successFormatter,
        ?callable $validator = null
    ): array {
        $startTime = microtime(true);
        $requestId = null;
        $result = null;
        $exception = null;

        try {
            // ログエントリ作成（best-effort）
            $requestId = $this->createLogEntry($featureType, $params);

            $payload = $this->messageBuilder->build($buildType, $params);

            $response = $this->callApi($payload, array_merge(['request_type' => $featureType], $params));

            if (!$response['success']) {
                $result = $response;
                return $response;
            }

            $data = $response['data'];

            // 追加検証
            if ($validator) {
                try {
                    $validator($data);
                } catch (\Exception $e) {
                    Log::warning("AI response validation failed for {$featureType}", ['data' => $data, 'error' => $e->getMessage()]);
                    $result = [
                        'success' => false,
                        'error' => 'Invalid response format from AI',
                        'status' => 500, // 内部エラー扱い
                    ];
                    return $result;
                }
            }

            // 成功レスポンス整形
            $formattedData = $successFormatter($data);

            $result = array_merge(['success' => true], $formattedData);
            return $result;

        } catch (Throwable $e) {
            Log::error("{$featureType} failed", [
                'exception' => $e->getMessage(),
                'params' => $params,
            ]);

            $exception = $e;
            $result = [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 500,
            ];
            return $result;
        } finally {
            // ログ更新（best-effort）
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $isSuccess = is_array($result) && (($result['success'] ?? false) === true);

            if ($requestId) {
                if ($isSuccess) {
                    $this->updateLogSuccess($requestId, $result, $durationMs);
                } else {
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

            Log::error('Topic AI API failed', [
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
                'status' => 502, // Bad Gateway的な扱い
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
                'status' => 502,
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
        $providerMessage = data_get($jsonBody, 'error.message');

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
    private function updateLogSuccess(string $requestId, array $responseData, int $durationMs): void
    {
        try {
            // 成功結果自体をそのまま保存したくない場合はここでフィルタリングも可能
            // 例: topicsやinfoなどの主要データのみ残すなど

            // `where()->update()` は Eloquent casts を通らないため、JSON列は明示的にエンコードする
            $encodedResponseData = json_encode($responseData, JSON_UNESCAPED_UNICODE);
            if ($encodedResponseData === false) {
                 $encodedResponseData = null;
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
        string $requestId,
        string $errorMessage,
        ?int $statusCode,
        int $durationMs
    ): void {
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
