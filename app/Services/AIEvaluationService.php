<?php

namespace App\Services;

use App\Models\Debate;
use App\Services\Traits\HandlesOpenRouterRetry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Throwable;

class AIEvaluationService
{
    use HandlesOpenRouterRetry;

    const API_TIMEOUT_SECONDS = 300;

    public function __construct(private DebateService $debateService)
    {
        //
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
        $evidenceAllowed = (bool) $room->evidence_allowed;

        // 1. ディベートメッセージを取得し、1つの文字列にまとめる
        $transcript = $debate->messages
            ->map(function ($msg) use ($debate, $language) {
                // ターン名を取得
                $turns = $this->debateService->getFormat($debate);
                $turnName = $turns[$msg->turn]['name'] ?? 'None';

                // 言語に応じて話者名を切り替え
                $speaker = ($language === 'japanese')
                    ? ($msg->user_id === $debate->affirmative_user_id ? '肯定側' : '否定側')
                    : ($msg->user_id === $debate->affirmative_user_id ? 'Affirmative' : 'Negative');

                // "[パート名] [話者] メッセージ内容" の形式にまとめる
                return sprintf(
                    "[%s] [%s] %s",
                    $turnName,
                    $speaker,
                    $msg->message
                );
            })
            ->implode("\n");

        // 言語と証拠利用可否に基づいてプロンプトキーを選択
        $basePromptKey = ($language === 'english') ? 'ai_prompts.debate_evaluation_en' : 'ai_prompts.debate_evaluation_ja';

        // フリーフォーマットの場合は専用プロンプトを使用
        if ($room->isFreeFormat()) {
            $promptKey = ($language === 'english') ? 'ai_prompts.debate_evaluation_free_en' : 'ai_prompts.debate_evaluation_free_ja';
        } else {
            $promptKey = $evidenceAllowed ? $basePromptKey : $basePromptKey . '_no_evidence';
        }

        Log::debug($promptKey);
        // 設定ファイルからプロンプトテンプレートを取得
        $promptTemplate = Config::get($promptKey);
        if (!$promptTemplate) {
            Log::error('AI prompt template not found in config.', ['key' => $promptKey, 'debate_id' => $debate->id, 'language' => $language, 'evidence_allowed' => $evidenceAllowed]);
            $promptTemplate = Config::get($basePromptKey);
            if (!$promptTemplate) {
                Log::error('Base AI prompt template also not found.', ['key' => $basePromptKey, 'debate_id' => $debate->id]);
                return $this->getDefaultResponse("プロンプトテンプレートが見つかりません ({$promptKey} も {$basePromptKey} も見つかりません)", $language);
            }
            Log::warning('Specific prompt not found, falling back to base prompt.', ['key' => $promptKey, 'fallback_key' => $basePromptKey, 'debate_id' => $debate->id]);
        }

        // プロンプトに変数を埋め込む
        $prompt = sprintf(
            $promptTemplate,
            $debate->room->topic, // 論題
            $transcript          // ディベート内容
        );

        // 言語に応じたJSONスキーマのEnum値を設定
        $winnerEnum = ($language === 'english') ? ['Affirmative', 'Negative'] : ['肯定側', '否定側'];

        // APIキーの確認
        $apiKey = Config::get('services.openrouter.api_key');
        if (empty($apiKey)) {
            Log::error('OpenRouter API key is not configured for evaluation.', ['debate_id' => $debate->id]);
            return $this->getDefaultResponse("AI評価サービスが正しく設定されていません", $language);
        }

        // 3. OpenRouter APIを呼び出す
        $retryAttempt = 0;
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'Debate Evaluation System',
            'Content-Type' => 'application/json',
        ])
            ->timeout(self::API_TIMEOUT_SECONDS)
            ->retry(3, function ($attempt, $exception) use (&$retryAttempt, $debate, $language) {
                $retryAttempt = $attempt;
                $shouldRetry = $this->shouldRetry($exception);

                if ($shouldRetry) {
                    $delayMs = $this->calculateBackoffDelay($retryAttempt);
                    Log::warning('OpenRouter API retry attempt (evaluation)', [
                        'debate_id' => $debate->id,
                        'attempt' => $retryAttempt,
                        'max_attempts' => 3,
                        'error' => $exception->getMessage(),
                        'delay_ms' => $delayMs,
                        'language' => $language,
                    ]);
                    usleep($delayMs * 1000); // マイクロ秒に変換
                }

                return $shouldRetry;
            }, throw: false)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => Config::get('services.openrouter.evaluation_model'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'reasoning' => [
                    'enabled' => true,
                ],
                'temperature' => 0.2,
                'max_tokens' => 30000,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'debateEvaluation',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'isAnalyzable' => [
                                    'type' => 'boolean',
                                ],
                                'analysis' => [
                                    'type' => ['string', 'null'],
                                ],
                                'reason' => [
                                    'type' => ['string', 'null'],
                                ],
                                'winner' => [
                                    'type' => ['string', 'null'],
                                    'enum' => $winnerEnum,
                                ],
                                'feedbackForAffirmative' => [
                                    'type' => ['string', 'null'],
                                ],
                                'feedbackForNegative' => [
                                    'type' => ['string', 'null'],
                                ]
                            ],
                            'required' => ['isAnalyzable', 'analysis', 'reason', 'winner', 'feedbackForAffirmative', 'feedbackForNegative'],
                            'additionalProperties' => false
                        ]
                    ]
                ]
            ]);

        // 4. レスポンスを処理
        if ($response->failed()) {
            Log::error('OpenRouter API Error after retries (evaluation)', [
                'response' => $response->json(),
                'status' => $response->status(),
                'debate_id' => $debate->id,
                'language' => $language,
                'retry_attempts' => $retryAttempt,
            ]);
            return $this->getDefaultResponse("AI APIとの通信に失敗しました", $language);
        }

        $message = $response->json('choices.0.message');
        $aiResponseContent = $message['content'] ?? null;
        $reasoning = $message['reasoning'] ?? null;

        if ($reasoning) {
            Log::debug('AI Reasoning received', [
                'debate_id' => $debate->id,
                'reasoning' => $reasoning,
            ]);
        }

        // JSONブロックを正規表現で抽出
        preg_match('/```json\s*(.*?)\s*```/s', $aiResponseContent, $matches);
        $jsonString = $matches[1] ?? $aiResponseContent;

        $parsedData = json_decode($jsonString, true);

        // パース失敗時のハンドリング
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMessage = json_last_error_msg();
            Log::error('Failed to parse AI response JSON', [
                'error' => $errorMessage,
                'response_content' => $aiResponseContent,
                'reasoning' => $reasoning,
                'debate_id' => $debate->id,
                'language' => $language
            ]);
            return $this->getDefaultResponse("AIからの応答の解析に失敗しました: " . $errorMessage, $language);
        }

        // 5. データを変換
        $winner = null;

        if (!empty($parsedData['isAnalyzable']) && isset($parsedData['winner'])) {
            // 言語に応じて winner の値を変換
            if ($language === 'english') {
                $winner = ($parsedData['winner'] === 'Affirmative') ? 'affirmative' : 'negative';
            } else {
                $winner = ($parsedData['winner'] === '肯定側') ? 'affirmative' : 'negative';
            }
        }

        // 評価データを構築
        $evaluationData = [
            'is_analyzable' => $parsedData['isAnalyzable'] ?? false,
            'winner' => $winner,
            'analysis' => (!empty($parsedData['isAnalyzable']) ? ($parsedData['analysis'] ?? 'Analysis unavailable') : ($language === 'japanese' ? '評価できませんでした' : 'Evaluation not possible')),
            'reason' => (!empty($parsedData['isAnalyzable']) ? ($parsedData['reason'] ?? 'Reason unavailable') : ($language === 'japanese' ? '評価できませんでした' : 'Evaluation not possible')),
            'feedback_for_affirmative' => (!empty($parsedData['isAnalyzable']) ? ($parsedData['feedbackForAffirmative'] ?? 'Feedback unavailable') : ($language === 'japanese' ? '評価できませんでした' : 'Evaluation not possible')),
            'feedback_for_negative' => (!empty($parsedData['isAnalyzable']) ? ($parsedData['feedbackForNegative'] ?? 'Feedback unavailable') : ($language === 'japanese' ? '評価できませんでした' : 'Evaluation not possible')),
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
}
