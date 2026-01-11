<?php

namespace App\Services;

use App\Models\Debate;
use App\Services\OpenRouter\DebateOpponentMessageBuilder;
use App\Services\OpenRouter\OpenRouterClient;
use App\Services\OpenRouter\OpenRouterContentNormalizer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Throwable;

class AIService
{
    private OpenRouterClient $openRouterClient;
    private DebateOpponentMessageBuilder $messageBuilder;

    const DEFAULT_TEMPERATURE = 0.7;
    const MAX_TOKENS = 25000;
    const API_TIMEOUT_SECONDS = 240;

    public function __construct(
        OpenRouterClient $openRouterClient,
        DebateOpponentMessageBuilder $messageBuilder
    ) {
        $this->openRouterClient = $openRouterClient;
        $this->messageBuilder = $messageBuilder;
    }

    /**
     * ディベートの状況に基づいてAIの応答を生成する
     *
     * @param Debate $debate
     * @return string
     * @throws \Exception
     */
    public function generateResponse(Debate $debate): string
    {
        try {
            $payload = $this->messageBuilder->build($debate);
            $options = [
                'timeout_seconds' => (int) Config::get('services.openrouter.timeout_seconds', self::API_TIMEOUT_SECONDS),
                'max_attempts' => (int) Config::get('services.openrouter.max_attempts', 3),
                'temperature' => (float) Config::get('services.openrouter.opponent_temperature', self::DEFAULT_TEMPERATURE),
                'max_tokens' => (int) Config::get('services.openrouter.opponent_max_tokens', self::MAX_TOKENS),
                // OpenRouter 側のセッション識別子。ログ/レート制御/分析に使える。
                'session_id' => 'debate:' . $debate->id,
                'metadata' => [
                    'debate_id' => (string) $debate->id,
                    'turn' => (string) $debate->current_turn,
                    'type' => 'opponent',
                ],
            ];

            $reasoningEnabled = Config::get('services.openrouter.opponent_reasoning_enabled');
            if ($reasoningEnabled === null || $reasoningEnabled === '') {
                $reasoningEnabled = Config::get('services.openrouter.reasoning_enabled', false);
            }
            $reasoningEnabled = $this->configBoolValue($reasoningEnabled, false);

            if ($reasoningEnabled) {
                // 思考過程は保存/表示しない前提なので、返すなら exclude してコストを抑える。
                $options['reasoning'] = ['enabled' => true, 'exclude' => true];
            }

            $response = $this->openRouterClient->chatCompletions($payload, $options, [
                'debate_id' => $debate->id,
                'request_type' => 'opponent',
            ]);

            if ($response->successful()) {
                $message = $response->json('choices.0.message');
                $content = OpenRouterContentNormalizer::toStringOrNull($message['content'] ?? null);
                $reasoning = $message['reasoning'] ?? null;

                if ($reasoning && $this->configBool('services.openrouter.log_reasoning', false)) {
                    Log::debug('AI Reasoning for debate opponent', [
                        'debate_id' => $debate->id,
                        'reasoning' => $reasoning,
                    ]);
                }

                if (empty($content)) {
                    Log::warning('OpenRouter API returned empty content', [
                        'debate_id' => $debate->id,
                        'response' => $response->json(),
                    ]);
                    return $this->getFallbackResponse($debate->room->language ?? 'japanese');
                }

                Log::info('Received AI response successfully', ['debate_id' => $debate->id]);
                return trim($content);
            }

            // HTTPエラー（throw: false のため、ここに到達する。ただし ConnectionException の場合は catch へ飛ぶ）
            Log::error('OpenRouter API Error after retries', [
                'debate_id' => $debate->id,
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);
            return $this->getFallbackResponse($debate->room->language ?? 'japanese', 'Status: ' . $response->status());
        } catch (Throwable $e) {
            // ConnectionException や、その他予期せぬ例外（message builder内のエラー等）
            Log::error('Error generating AI response', [
                'debate_id' => $debate->id,
                'exception' => $e->getMessage(),
                'trace' => mb_strimwidth($e->getTraceAsString(), 0, 2000, '...'),
            ]);
            return $this->getFallbackResponse($debate->room->language ?? 'japanese', $e->getMessage());
        }
    }

    /**
     * エラー時や空応答時の代替メッセージを取得
     */
    private function getFallbackResponse(string $language, ?string $errorInfo = null): string
    {
        $locale = $language === 'english' ? 'en' : 'ja';
        $baseMessage = __('ai_debate.fallback_response', [], $locale);

        if ($errorInfo) {
            $techDetail = __('ai_debate.technical_issue', [], $locale);
            return $baseMessage . " " . $techDetail;
        }
        return $baseMessage;
    }

    private function configBool(string $key, bool $default = false): bool
    {
        return $this->configBoolValue(Config::get($key), $default);
    }

    private function configBoolValue(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $filtered ?? $default;
    }
}
