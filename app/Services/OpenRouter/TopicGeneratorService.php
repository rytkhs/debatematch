<?php

namespace App\Services\OpenRouter;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
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
        try {
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
                return $response;
            }

            $data = $response['data'];

            // topics配列の検証
            if (!isset($data['topics']) || !is_array($data['topics'])) {
                Log::warning('Topic generation returned invalid format', ['data' => $data]);
                return [
                    'success' => false,
                    'error' => 'Invalid response format from AI',
                ];
            }

            return [
                'success' => true,
                'topics' => $data['topics'],
            ];
        } catch (Throwable $e) {
            Log::error('Topic generation failed', [
                'exception' => $e->getMessage(),
                'keywords' => $keywords,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 論題の背景情報を取得
     */
    public function getTopicInfo(string $topic, string $language): array
    {
        try {
            $payload = $this->messageBuilder->build('info', [
                'topic' => $topic,
                'language' => $language,
            ]);

            $response = $this->callApi($payload, [
                'request_type' => 'topic_info',
                'topic' => $topic,
            ]);

            if (!$response['success']) {
                return $response;
            }

            $data = $response['data'];

            if (!isset($data['info']) || !is_array($data['info'])) {
                Log::warning('Topic info returned invalid format', ['data' => $data]);
                return [
                    'success' => false,
                    'error' => 'Invalid response format from AI',
                ];
            }

            return [
                'success' => true,
                'info' => $data['info'],
            ];
        } catch (Throwable $e) {
            Log::error('Topic info retrieval failed', [
                'exception' => $e->getMessage(),
                'topic' => $topic,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
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
}
