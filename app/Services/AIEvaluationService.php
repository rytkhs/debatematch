<?php

namespace App\Services;

use App\Models\Debate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIEvaluationService
{
    /**
     * ディベートのデータを元にAI評価を実施し、評価結果を返す
     *
     * @param Debate $debate
     * @return array
     */
    public static function evaluate(Debate $debate): array
    {
        // 1. ディベートメッセージを取得し、1つの文字列にまとめる
        $transcript = $debate->messages
            ->map(function ($msg) use ($debate) {
                // ターン名を取得
                $turns = $debate->getFormat();
                $turnName = $turns[$msg->turn]['name'] ?? '無し';

                // "[パート名] [話者] メッセージ内容" の形式にまとめる
                return sprintf(
                    "[%s] [%s] %s",
                    $turnName,
                    $msg->user_id === $debate->affirmative_user_id ? '肯定側' : '否定側',
                    $msg->message
                );
            })
            ->implode("\n");

        $prompt = <<<EOT
＜事前指示＞
あなたは競技ディベートの公式ジャッジとして振る舞います。 審査基準は以下のとおりです。公平性・客観性・説明責任を常に意識してください。

1. ジャッジの基本理念
・「公平性」を重視すること（両チームの議論を偏りなく扱う）。
・「客観性」を重視すること（実証的・合理的に判断し、主観的感覚や好みには流されない）。
・「説明責任」を重視すること（ジャッジ理由を明確に示し、ディベーターの成長を助けるアドバイスを行う）。

2. 勝ち負け判定の基本
・論題どおりに政策を採用（肯定側）した際のメリットとデメリットを、試合中に提示された議論をもとに客観的に比較する。
・メリットの強さがデメリットの強さを上回れば肯定側の勝利、上回らなければ否定側の勝利。
・引き分けは許されない（差が判別できない場合は否定側の勝利とする）。

3. 具体的な判定ステップ
(1) 試合終盤まで有効だった論点をリストアップ。
(2) それぞれの論点について「もっともらしさ（蓋然性）」を判定する。
(3) それぞれの論点の「価値（重要度）」を判定する。
(4) もっともらしさ × 価値 で各論点の強さを総合的に判断する。
(5) 肯定側メリットの総合強さと否定側デメリットの総合強さを比較し、上回れば肯定側、そうでなければ否定側の勝利とする。

4. 分析に値しないディベートの扱い
迷惑行為防止の為、以下の場合のみ、分析に値しないと判断し、isAnalyzableをfalseとしてください：
・論題や議論がその体をなしていない（例：「ああああああ」「意味不明な文字列」など）
・全く別の話題で議論が行われている（例：論題は「死刑制度を廃止すべきか」なのに、全く関係のない「学校給食の是非」について議論している）
・その他、本来の目的(ディベート)とは異なる行為をしているなど、明らかに迷惑行為の意図がある

このルールを踏まえて、以下のディベート内容を評価してください。
ディベートの内容を入力として受け取り、上記の5ステップに則り、必ず最終的な勝者を決定してください。

────────────────────────────────────────

＜あなたへの指示＞
1. まず、ディベートの論題と議論の内容が明らかに分析に値しないものであるかを判断してください。「分析に値しないディベートの扱い」の条件に当てはまる場合のみ、isAnalyzableをfalseとし、他のすべての項目に null を返してください。それ以外の場合はtrueとし、以下の手順で評価を進めてください。
2. 次に、ユーザーが与えた「ディベートで提出された主な論点」をリストアップしてください。
3. それぞれの論点に対して「もっともらしさ（根拠の強さ、証拠の有無、反論や再反論の成否）」を判断し、評価してください。
4 各論点の「価値（どれほど深刻・重要な影響があるのか、議論中の意義づけはどうか）」を評価してください。
5. もっともらしさ × 価値 で論点ごとの強さを算出し、メリットとデメリットの総合強さを比較してください。
6. 公平・客観・説明責任に基づき、勝者を「肯定側」か「否定側」で一意に決定してください（引き分けは不可）。
7. 最終出力を、以下のJSON形式で示してください。analysis,reason,feedbackForAffirmative,feedbackForNegativeは、必要であれば適宜マークダウンで記述してください。すべて日本語で出力してください。

────────────────────────────────────────
＜出力フォーマットの指定（必ずこの構造を維持してください。）＞

{
  "isAnalyzable": true/false,
  "analysis": "具体的な議論の分析。どのような論点やメリット/デメリットがあり、そのそれぞれがどの程度もっともらしく、重要と判断したかを詳細かつ具体的に説明。明らかに分析に値しない場合のみ null",
  "reason": "最終的な勝敗判定の理由。どのように各論点を評価し、どのように比較したかを詳細かつ具体的に説明。明らかに分析に値しない場合のみ null",
  "winner": "肯定側/否定側。明らかに分析に値しない場合のみ null",
  "feedbackForAffirmative": "肯定側チームへの建設的なアドバイス・フィードバック。議論の質や論点の明確さ、論証の強化方法などについて具体的に記述。明らかに分析に値しない場合のみ null",
  "feedbackForNegative": "否定側チームへの建設的なアドバイス・フィードバック。議論の質や論点の明確さ、論証の強化方法などについて具体的に記述。明らかに分析に値しない場合のみ null"
}

────────────────────────────────────────
<ディベート内容>
論題：{$debate->room->topic}

$transcript
EOT;

        // 3. OpenRouter APIを呼び出す
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'Debate Evaluation System',
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'google/gemini-2.0-flash-thinking-exp:free',
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
                                'description' => 'ディベートが分析可能かどうかを示すフラグ。分析可能な場合はtrue、そうでない場合はfalse。'
                            ],
                            'analysis' => [
                                'type' => ['string', 'null'],
                                'description' => '具体的な議論の分析。どのような論点やメリット/デメリットがあり、そのそれぞれがどの程度もっともらしく、重要と判断したかを詳細かつ具体的に説明。isAnalyzableがfalseの場合はnull。'
                            ],
                            'reason' => [
                                'type' => ['string', 'null'],
                                'description' => '最終的な勝敗判定の理由。どのように各論点を評価し、どのように比較したかを詳細かつ具体的に説明。isAnalyzableがfalseの場合はnull。'
                            ],
                            'winner' => [
                                'type' => ['string', 'null'],
                                'enum' => ['肯定側', '否定側'],
                                'description' => '勝者は肯定側か否定側か。isAnalyzableがfalseの場合はnull。'
                            ],
                            'feedbackForAffirmative' => [
                                'type' => ['string', 'null'],
                                'description' => '肯定側チームへの建設的なアドバイス・フィードバック。isAnalyzableがfalseの場合はnull。'
                            ],
                            'feedbackForNegative' => [
                                'type' => ['string', 'null'],
                                'description' => '否定側チームへの建設的なアドバイス・フィードバック。isAnalyzableがfalseの場合はnull。'
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
            Log::error('OpenRouter API Error', ['response' => $response->json()]);
            return self::getDefaultResponse();
        }

        $aiResponse = $response->json('choices.0.message.content');

        // JSONブロックを正規表現で抽出（```jsonと```のトリミング）
        preg_match('/```json\s*(.*?)\s*```/s', $aiResponse, $matches);
        $jsonString = $matches[1] ?? $aiResponse;

        $parsedData = json_decode($jsonString, true);

        // 万が一パースに失敗した場合の簡易ハンドリング
        if (!is_array($parsedData)) {
            return self::getDefaultResponse();
        }

        // 5. データを変換
        $winner = ($parsedData['winner'] === '肯定側') ? 'affirmative' : 'negative';

        $evaluationData = [
            'is_analyzable' => $parsedData['isAnalyzable'],
            'winner' => $parsedData['isAnalyzable'] ? $winner : null,
            'analysis' => $parsedData['isAnalyzable'] ? $parsedData['analysis'] : '評価できませんでした',
            'reason' => $parsedData['isAnalyzable'] ? $parsedData['reason'] : '評価できませんでした',
            'feedback_for_affirmative' => $parsedData['isAnalyzable'] ? $parsedData['feedbackForAffirmative'] : '評価できませんでした',
            'feedback_for_negative' => $parsedData['isAnalyzable'] ? $parsedData['feedbackForNegative'] : '評価できませんでした',
        ];

        return $evaluationData;
    }

    private static function getDefaultResponse(): array
    {
        return [
            "isAnalyzable" => false,
            "analysis" => "解析に失敗しました: " . json_last_error_msg(),
            "reason" => "JSON形式の解析に失敗しました",
            "winner" => "不明",
            "feedbackForAffirmative" => "システムエラー",
            "feedbackForNegative" => "システムエラー",
        ];
    }
}
