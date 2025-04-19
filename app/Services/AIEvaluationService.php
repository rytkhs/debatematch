<?php

namespace App\Services;

use App\Models\Debate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class AIEvaluationService
{
    protected $debateService;

    public function __construct(DebateService $debateService)
    {
        $this->debateService = $debateService;
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

        // 言語に応じたプロンプトキーを選択
        $promptKey = ($language === 'english') ? 'ai_prompts.debate_evaluation_en' : 'ai_prompts.debate_evaluation_ja';

        // 設定ファイルからプロンプトテンプレートを取得
        $promptTemplate = Config::get($promptKey);
        if (!$promptTemplate) {
            Log::error('AI prompt template not found in config.', ['key' => $promptKey]);
            return $this->getDefaultResponse("プロンプトテンプレートが見つかりません ({$promptKey})");
        }

        // プロンプトに変数を埋め込む
        $prompt = sprintf(
            $promptTemplate,
            $debate->room->topic, // 論題
            $transcript          // ディベート内容
        );

        // 言語に応じたJSONスキーマのEnum値を設定
        $winnerEnum = ($language === 'english') ? ['Affirmative', 'Negative'] : ['肯定側', '否定側'];

        // 3. OpenRouter APIを呼び出す
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'Debate Evaluation System',
            'Content-Type' => 'application/json',
        ])
        ->timeout(240)
        ->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => env('OPENROUTER_EVALUATION_MODEL'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.2,
            'max_tokens' => 5000,
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
            Log::error('OpenRouter API Error', [
                'response' => $response->json(),
                'status' => $response->status(),
                'debate_id' => $debate->id,
                'language' => $language
            ]);
            return $this->getDefaultResponse("AI APIとの通信に失敗しました");
        }

        $aiResponseContent = $response->json('choices.0.message.content');

        // JSONブロックを正規表現で抽出（```jsonと```のトリミング）
        preg_match('/```json\s*(.*?)\s*```/s', $aiResponseContent, $matches);
        $jsonString = $matches[1] ?? $aiResponseContent; // マッチしない場合はそのまま

        $parsedData = json_decode($jsonString, true);

        // パース失敗時のハンドリング
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMessage = json_last_error_msg();
            Log::error('Failed to parse AI response JSON', [
                'error' => $errorMessage,
                'response_content' => $aiResponseContent,
                'debate_id' => $debate->id,
                'language' => $language
            ]);
            return $this->getDefaultResponse("AIからの応答の解析に失敗しました: " . $errorMessage);
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

    // getDefaultResponse メソッドの引数名を変更し、汎用的に
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
