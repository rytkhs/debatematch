<?php

namespace App\Services;

use App\Models\Debate;
use App\Services\OpenRouter\DebateEvaluationMessageBuilder;
use App\Services\OpenRouter\OpenRouterClient;
use App\Services\OpenRouter\OpenRouterContentNormalizer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Throwable;

class AIEvaluationService
{
    const API_TIMEOUT_SECONDS = 300;
    private const ALLOWED_WINNERS = ['affirmative', 'negative'];

    private OpenRouterClient $openRouterClient;
    private DebateEvaluationMessageBuilder $messageBuilder;

    public function __construct(
        OpenRouterClient $openRouterClient,
        DebateEvaluationMessageBuilder $messageBuilder
    ) {
        $this->openRouterClient = $openRouterClient;
        $this->messageBuilder = $messageBuilder;
    }

    /**
     * ディベートのデータを元にAI評価を実施し、評価結果を返す
     *
     * @param Debate $debate
     * @return array
     */
    public function evaluate(Debate $debate): array
    {
        $room = $debate->room;
        $language = $room->language ?? 'japanese';

        try {
            $payload = $this->messageBuilder->build($debate);
            $options = [
                'timeout_seconds' => (int) Config::get('services.openrouter.timeout_seconds', self::API_TIMEOUT_SECONDS),
                'max_attempts' => (int) Config::get('services.openrouter.max_attempts', 3),
                'temperature' => (float) Config::get('services.openrouter.evaluation_temperature', 0.2),
                'max_tokens' => (int) Config::get('services.openrouter.evaluation_max_tokens', 30000),
                // 評価リクエスト単位で追跡できるように、セッションIDを固定ルールで付ける。
                'session_id' => 'debate:' . $debate->id,
                // 外部APIへ渡る可能性があるため、最低限の識別情報だけを載せる。
                'metadata' => [
                    'debate_id' => (string) $debate->id,
                    'turn' => (string) $debate->current_turn,
                    'type' => 'evaluation',
                ],
            ];

            $reasoningEnabled = Config::get('services.openrouter.evaluation_reasoning_enabled');
            if ($reasoningEnabled === null || $reasoningEnabled === '') {
                $reasoningEnabled = Config::get('services.openrouter.reasoning_enabled', false);
            }
            $reasoningEnabled = $this->configBoolValue($reasoningEnabled, false);

            if ($reasoningEnabled) {
                // UI/保存に使わない推論は返させない
                $options['reasoning'] = ['enabled' => true, 'exclude' => true];
            }

            $response = $this->openRouterClient->chatCompletions($payload, $options, [
                'debate_id' => $debate->id,
                'request_type' => 'evaluation',
            ]);
        } catch (Throwable $e) {
            Log::error('Error generating AI evaluation', [
                'debate_id' => $debate->id,
                'exception' => $e->getMessage(),
            ]);

            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, 'OpenRouter API key')) {
                $errorMessage = $language === 'japanese'
                    ? 'AI評価サービスが正しく設定されていません'
                    : 'AI evaluation service is not configured.';
            }
            if ($language === 'japanese' && str_contains($errorMessage, 'template')) {
                $errorMessage = 'プロンプトテンプレートが見つかりません';
            }

            return $this->getDefaultResponse($errorMessage, $language);
        }

        if ($response->failed()) {
            Log::error('OpenRouter API Error after retries (evaluation)', [
                'response' => $response->json(),
                'status' => $response->status(),
                'debate_id' => $debate->id,
                'language' => $language,
            ]);
            return $this->getDefaultResponse("AI APIとの通信に失敗しました", $language);
        }

        $message = $response->json('choices.0.message');
        $aiResponseContent = OpenRouterContentNormalizer::toStringOrNull($message['content'] ?? null);
        $reasoning = $message['reasoning'] ?? null;

        if ($reasoning && $this->configBool('services.openrouter.log_reasoning', false)) {
            Log::debug('AI Reasoning received', [
                'debate_id' => $debate->id,
                'reasoning' => $reasoning,
            ]);
        }

        if ($aiResponseContent === null || trim($aiResponseContent) === '') {
            Log::error('OpenRouter API returned empty evaluation content', [
                'debate_id' => $debate->id,
                'language' => $language,
            ]);
            return $this->getDefaultResponse("AIからの応答の解析に失敗しました: empty content", $language);
        }

        // まずは「素直にJSONとして読める」ケースを高速に処理し、失敗時のみ修復フローへ回す。
        $decodeError = null;
        $parsedData = $this->decodeResponseJson($aiResponseContent, $decodeError);
        $repairAttempted = false;
        if ($parsedData === null) {
            $repairAttempted = true;
            $repairError = null;
            // 修復は追加のAPI呼び出しになるため、最小回数で済むよう最初の失敗時だけ試す。
            $parsedData = $this->attemptJsonRepair($aiResponseContent, $payload, $debate, $language, $repairError);
            if ($parsedData === null) {
                $finalError = $repairError ?? $decodeError ?? 'Unknown JSON parse error';
                Log::error('Failed to parse AI response JSON', [
                    'error' => $finalError,
                    'response_content' => $aiResponseContent,
                    'debate_id' => $debate->id,
                    'language' => $language,
                ]);
                return $this->getDefaultResponse("AIからの応答の解析に失敗しました: " . $finalError, $language);
            }
        }

        $validationError = null;
        // schema(必須キー/型)の整合性は、ここで一括してチェックする。
        $normalizedData = $this->normalizeParsedData($parsedData, $validationError);
        if ($normalizedData === null) {
            if (!$repairAttempted) {
                $repairAttempted = true;
                $repairError = null;
                $parsedData = $this->attemptJsonRepair($aiResponseContent, $payload, $debate, $language, $repairError);
                if ($parsedData === null) {
                    $finalError = $repairError ?? $validationError ?? 'Unknown validation error';
                    Log::error('Failed to repair AI response JSON', [
                        'error' => $finalError,
                        'response_content' => $aiResponseContent,
                        'debate_id' => $debate->id,
                        'language' => $language,
                    ]);
                    return $this->getDefaultResponse("AIからの応答の検証に失敗しました: " . $finalError, $language);
                }
                $validationError = null;
                $normalizedData = $this->normalizeParsedData($parsedData, $validationError);
            }

            if ($normalizedData === null) {
                $finalError = $validationError ?? 'Unknown validation error';
                Log::error('Invalid AI response JSON', [
                    'error' => $finalError,
                    'response_content' => $aiResponseContent,
                    'debate_id' => $debate->id,
                    'language' => $language,
                ]);
                return $this->getDefaultResponse("AIからの応答の検証に失敗しました: " . $finalError, $language);
            }
        }

        $parsedData = $normalizedData;

        $isAnalyzable = (bool) $parsedData['isAnalyzable'];
        $winner = $isAnalyzable ? $parsedData['winner'] : null;

        // 評価データを構築
        $evaluationData = [
            'is_analyzable' => $isAnalyzable,
            'winner' => $winner,
            'analysis' => ($isAnalyzable ? ($parsedData['analysis'] ?? 'Analysis unavailable') : ($language === 'japanese' ? '評価できませんでした' : 'Evaluation not possible')),
            'reason' => ($isAnalyzable ? ($parsedData['reason'] ?? 'Reason unavailable') : ($language === 'japanese' ? '評価できませんでした' : 'Evaluation not possible')),
            'feedback_for_affirmative' => ($isAnalyzable ? ($parsedData['feedbackForAffirmative'] ?? 'Feedback unavailable') : ($language === 'japanese' ? '評価できませんでした' : 'Evaluation not possible')),
            'feedback_for_negative' => ($isAnalyzable ? ($parsedData['feedbackForNegative'] ?? 'Feedback unavailable') : ($language === 'japanese' ? '評価できませんでした' : 'Evaluation not possible')),
        ];

        return $evaluationData;
    }

    private function getDefaultResponse(string $message = "An error occurred while processing", string $language = 'english'): array
    {
        $analysisMsg = $language === 'japanese' ? "解析に失敗しました" : "Analysis failed";
        $feedbackMsg = $language === 'japanese' ? "システムエラー" : "System error";

        return [
            "is_analyzable" => false,
            "analysis" => $analysisMsg,
            "reason" => $message,
            "winner" => null,
            "feedback_for_affirmative" => $feedbackMsg,
            "feedback_for_negative" => $feedbackMsg,
        ];
    }

    private function normalizeParsedData(array $data, ?string &$errorMessage = null): ?array
    {
        $errorMessage = null;
        $requiredKeys = [
            'isAnalyzable',
            'analysis',
            'reason',
            'winner',
            'feedbackForAffirmative',
            'feedbackForNegative',
        ];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                $errorMessage = 'Missing key: ' . $key;
                return null;
            }
        }

        if (!is_bool($data['isAnalyzable'])) {
            $errorMessage = 'Invalid type for isAnalyzable.';
            return null;
        }

        $stringFields = [
            'analysis',
            'reason',
            'feedbackForAffirmative',
            'feedbackForNegative',
        ];

        foreach ($stringFields as $field) {
            if (!$this->isNullableString($data[$field])) {
                $errorMessage = 'Invalid type for ' . $field . '.';
                return null;
            }
        }

        $normalized = $data;
        $isAnalyzable = $data['isAnalyzable'];
        $normalizedWinner = $this->normalizeWinnerValue($data['winner']);
        if ($isAnalyzable) {
            if ($normalizedWinner === null) {
                $errorMessage = 'Invalid winner value.';
                return null;
            }
            $normalized['winner'] = $normalizedWinner;
        } else {
            $normalized['winner'] = null;
        }

        return $normalized;
    }

    private function normalizeWinnerValue(mixed $winner): ?string
    {
        if ($winner === null) {
            return null;
        }
        if (!is_string($winner)) {
            return null;
        }

        $normalized = strtolower(trim($winner));
        if (in_array($normalized, self::ALLOWED_WINNERS, true)) {
            return $normalized;
        }

        return null;
    }

    private function isNullableString(mixed $value): bool
    {
        return $value === null || is_string($value);
    }

    private function decodeResponseJson(string $content, ?string &$errorMessage = null): ?array
    {
        $errorMessage = null;
        $trimmed = trim($content);

        if ($trimmed === '') {
            $errorMessage = 'Empty content.';
            return null;
        }

        // LLMが ```json ... ``` で返すケースをまず拾う（素の JSON より頻度が高い）。
        $decoded = $this->tryDecodeFencedJson($trimmed, $errorMessage);
        if ($decoded !== null) {
            return $decoded;
        }

        // 先頭が { なら、余計な前置きがない可能性が高い。
        if (str_starts_with($trimmed, '{')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            $errorMessage = json_last_error_msg();
        }

        // 多少の説明文が混ざっても復旧できるよう、{} の最外郭を切り出して試す。
        $decoded = $this->tryDecodeJsonSubstring($trimmed, $errorMessage);
        if ($decoded !== null) {
            return $decoded;
        }

        $errorMessage = $errorMessage ?? 'No JSON content found.';
        return null;
    }

    private function tryDecodeFencedJson(string $content, ?string &$errorMessage = null): ?array
    {
        if (!preg_match('/```(?:json)?\s*(.*?)\s*```/is', $content, $matches)) {
            return null;
        }

        $candidate = trim($matches[1]);
        if ($candidate === '') {
            $errorMessage = 'No JSON content found.';
            return null;
        }

        $decoded = json_decode($candidate, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        $errorMessage = json_last_error_msg();
        return null;
    }

    private function tryDecodeJsonSubstring(string $content, ?string &$errorMessage = null): ?array
    {
        $firstBrace = strpos($content, '{');
        $lastBrace = strrpos($content, '}');
        if ($firstBrace === false || $lastBrace === false || $lastBrace <= $firstBrace) {
            return null;
        }

        $candidate = substr($content, $firstBrace, $lastBrace - $firstBrace + 1);
        $decoded = json_decode($candidate, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        $errorMessage = json_last_error_msg();
        return null;
    }

    private function attemptJsonRepair(
        string $content,
        array $payload,
        Debate $debate,
        string $language,
        ?string &$errorMessage = null
    ): ?array {
        $errorMessage = null;

        // 修復リクエストは「原文→JSONのみ」に限定し、モデルの自由度を極力減らす。
        $repairPayload = [
            'model' => $payload['model'] ?? Config::get('services.openrouter.evaluation_model'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->buildRepairSystemPrompt($language),
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildRepairUserPrompt($content),
                ],
            ],
        ];

        if (isset($payload['response_format'])) {
            $repairPayload['response_format'] = $payload['response_format'];
        }

        $options = [
            'timeout_seconds' => (int) Config::get('services.openrouter.timeout_seconds', self::API_TIMEOUT_SECONDS),
            // 修復はベストエフォートで十分。ここでの多重リトライは本処理の遅延を増やすだけになりやすい。
            'max_attempts' => 1,
            'temperature' => 0.0,
            'max_tokens' => (int) Config::get('services.openrouter.evaluation_repair_max_tokens', 1200),
            'session_id' => 'debate:' . $debate->id . ':evaluation_repair',
            'metadata' => [
                'debate_id' => (string) $debate->id,
                'type' => 'evaluation_repair',
            ],
        ];

        try {
            $response = $this->openRouterClient->chatCompletions($repairPayload, $options, [
                'debate_id' => $debate->id,
                'request_type' => 'evaluation_repair',
            ]);
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();
            Log::warning('OpenRouter JSON repair request failed', [
                'debate_id' => $debate->id,
                'error' => $errorMessage,
            ]);
            return null;
        }

        if ($response->failed()) {
            $errorMessage = 'Repair request failed with status ' . $response->status();
            Log::warning('OpenRouter JSON repair failed', [
                'debate_id' => $debate->id,
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);
            return null;
        }

        $repairedContent = OpenRouterContentNormalizer::toStringOrNull(
            $response->json('choices.0.message.content') ?? null
        );
        if ($repairedContent === null || trim($repairedContent) === '') {
            $errorMessage = 'Repair response was empty.';
            return null;
        }

        $decoded = $this->decodeResponseJson($repairedContent, $errorMessage);
        if ($decoded === null) {
            Log::warning('Failed to parse repaired JSON content', [
                'debate_id' => $debate->id,
                'error' => $errorMessage,
            ]);
        }

        return $decoded;
    }

    private function buildRepairSystemPrompt(string $language): string
    {
        if ($language === 'japanese') {
            return 'あなたはJSON修復ツールです。必須キーは isAnalyzable, analysis, reason, winner, feedbackForAffirmative, feedbackForNegative です。isAnalyzable は true/false。winner は null か "affirmative"/"negative" のいずれか。analysis/reason/feedbackForAffirmative/feedbackForNegative は文字列か null。必須キーをすべて含む有効なJSONのみを返してください。説明やマークダウンは不要です。';
        }

        return 'You are a JSON repair tool. Required keys: isAnalyzable, analysis, reason, winner, feedbackForAffirmative, feedbackForNegative. isAnalyzable is true/false. winner is null or one of: "affirmative", "negative". analysis/reason/feedbackForAffirmative/feedbackForNegative are string or null. Return only valid JSON with all required keys. No commentary or markdown.';
    }

    private function buildRepairUserPrompt(string $content): string
    {
        return "Fix the following content into valid JSON only:\n\n" . trim($content);
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
