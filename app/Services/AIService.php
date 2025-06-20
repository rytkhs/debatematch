<?php

namespace App\Services;

use App\Models\Debate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Throwable;

class AIService
{
    private string $apiKey;
    private string $model;
    private string $referer;
    private string $title;
    private int $aiUserId;

    const JAPANESE_CHARS_PER_MINUTE = 320;
    const ENGLISH_WORDS_PER_MINUTE = 160;
    const DEFAULT_TEMPERATURE = 0.7;
    const MAX_TOKENS = 12000;
    const API_TIMEOUT_SECONDS = 240;
    const FREE_FORMAT_RESPONSE_RATIO = 0.5; // フリーフォーマット時の応答長さ比率

    public function __construct(private DebateService $debateService)
    {
        $this->apiKey = Config::get('services.openrouter.api_key');
        $this->model = Config::get('services.openrouter.model', 'google/gemini-pro');
        $this->referer = Config::get('services.openrouter.referer', config('app.url'));
        $this->title = Config::get('services.openrouter.title', config('app.name'));
        $this->aiUserId = (int)config('app.ai_user_id', 1);
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
        if (empty($this->apiKey)) {
            Log::error('OpenRouter API key is not configured.');
            throw new \Exception('AI Service is not configured properly.');
        }

        try {
            $prompt = $this->buildPrompt($debate);

            Log::debug('Sending request to OpenRouter', [
                'debate_id' => $debate->id,
                'model' => $this->model,
                'prompt_length' => strlen($prompt)
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'HTTP-Referer' => $this->referer,
                'X-Title' => $this->title,
                'Content-Type' => 'application/json',
            ])
                ->timeout(self::API_TIMEOUT_SECONDS)
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => self::DEFAULT_TEMPERATURE,
                    'max_tokens' => self::MAX_TOKENS,
                ]);

            if ($response->failed()) {
                Log::error('OpenRouter API Error', [
                    'debate_id' => $debate->id,
                    'status' => $response->status(),
                    'response' => $response->json() ?? $response->body(),
                ]);
                throw new \Exception('Failed to get response from AI service. Status: ' . $response->status());
            }

            $content = $response->json('choices.0.message.content');

            if (empty($content)) {
                Log::warning('OpenRouter API returned empty content', [
                    'debate_id' => $debate->id,
                    'response' => $response->json(),
                ]);
                return $this->getFallbackResponse($debate->room->language ?? 'japanese');
            }

            Log::info('Received AI response successfully', ['debate_id' => $debate->id]);
            return trim($content);
        } catch (Throwable $e) {
            Log::error('Error generating AI response', [
                'debate_id' => $debate->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->getFallbackResponse($debate->room->language ?? 'japanese', $e->getMessage());
        }
    }

    /**
     * AIに送るプロンプトを構築する
     *
     * @param Debate $debate
     * @return string
     * @throws \Exception
     */
    private function buildPrompt(Debate $debate): string
    {
        $room = $debate->room;
        $language = $room->language ?? 'japanese';
        $topic = $room->topic;

        $aiRawSide = ($debate->affirmative_user_id === $this->aiUserId) ? 'affirmative' : 'negative';

        // ディベートフォーマットと現在のターン情報を取得（フリーフォーマット以外）
        $currentTurnName = '';
        $timeLimitMinutes = 180; // デフォルト値

        if (!$room->isFreeFormat()) {
            $format = $this->debateService->getFormat($debate);
            $currentTurnNumber = $debate->current_turn;
            $currentTurnInfo = $format[$currentTurnNumber] ?? null;

            if (!$currentTurnInfo) {
                Log::error("Could not get current turn info for debate {$debate->id}, turn {$currentTurnNumber}");
                throw new \Exception("Could not get current turn info for debate {$debate->id}, turn {$currentTurnNumber}");
            }

            $currentTurnName = $currentTurnInfo['name'];
            $timeLimitMinutes = $currentTurnInfo['duration'] / 60 ?? 180;
        } else {
            // フリーフォーマットの場合は、ルームの設定から時間制限を取得
            $format = $room->getDebateFormat();
            if (!empty($format) && isset($format[0]['duration'])) {
                $timeLimitMinutes = $format[0]['duration'] / 60;
            }
        }

        // 言語に応じた文字数/単語数制限の計算
        $characterLimit = $this->calculateCharacterLimit($timeLimitMinutes, $language, $room->isFreeFormat());

        // ディベート履歴を整形
        $history = $debate->messages()
            ->with('user')
            ->orderBy('created_at')
            ->get()
            ->map(function ($msg) use ($debate, $language, $room) {
                $speakerSide = ($msg->user_id === $debate->affirmative_user_id) ? 'affirmative' : 'negative';
                $speakerLabel = '';

                if ($language === 'japanese') {
                    $speakerLabel = ($speakerSide === 'affirmative' ? '肯定側' : '否定側');
                } else {
                    $speakerLabel = ($speakerSide === 'affirmative' ? 'Affirmative Side' : 'Negative Side');
                }

                // フリーフォーマットの場合はシンプルな履歴形式
                if ($room->isFreeFormat()) {
                    $messageContent = nl2br(e($msg->message));
                    return "{$speakerLabel}:\n{$messageContent}";
                } else {
                    // 通常フォーマットの場合は詳細な履歴形式
                    $format = $this->debateService->getFormat($debate);
                    $turnName = $format[$msg->turn]['name'] ?? 'unknown speech';
                    $messageContent = nl2br(e($msg->message));
                    return "[{$turnName}] {$speakerLabel}:\n{$messageContent}";
                }
            })
            ->implode("\n\n");

        // 言語に応じたプロンプトテンプレートを取得
        // フリーフォーマットの場合は専用のプロンプトを使用
        if ($room->isFreeFormat()) {
            $promptTemplateKey = ($language === 'japanese') ? 'ai_prompts.debate_ai_opponent_free_ja' : 'ai_prompts.debate_ai_opponent_free_en';
        } else {
            $promptTemplateKey = ($language === 'japanese') ? 'ai_prompts.debate_ai_opponent_ja' : 'ai_prompts.debate_ai_opponent_en';
        }
        $promptTemplate = Config::get($promptTemplateKey);

        if (!$promptTemplate) {
            Log::error("AI opponent prompt template not found for language: {$language}");
            throw new \Exception("AI opponent prompt template not configured for language: {$language}");
        }

        $aiSideName = ($language === 'japanese')
            ? (($aiRawSide === 'affirmative') ? '肯定側' : '否定側')
            : (($aiRawSide === 'affirmative') ? 'Affirmative' : 'Negative');

        // パラメータ置換の設定（フリーフォーマットと通常フォーマットで異なる）
        if ($room->isFreeFormat()) {
            // フリーフォーマット用のパラメータ置換
            $replacements = [
                '{resolution}' => $topic,
                '{ai_side}' => $aiSideName,
                '{time_limit_minutes}' => $timeLimitMinutes,
                '{debate_history}' => $history ?: (($language === 'japanese') ? 'まだ発言はありません。' : 'No speeches yet.'),
                '{character_limit}' => $characterLimit, // 文字数/単語数制限
            ];
        } else {
            // 通常フォーマット用のパラメータ置換
            // フォーマットの説明
            $debateFormatDescription = $this->buildFormatDescription($debate);

            $replacements = [
                '{resolution}' => $topic,
                '{ai_side}' => $aiSideName,
                '{debate_format_description}' => $debateFormatDescription,
                '{current_part_name}' => $currentTurnName,
                '{time_limit_minutes}' => $timeLimitMinutes,
                '{debate_history}' => $history ?: (($language === 'japanese') ? 'まだ発言はありません。' : 'No speeches yet.'),
                '{character_limit}' => $characterLimit, // 文字数/単語数制限
            ];
        }

        $prompt = str_replace(array_keys($replacements), array_values($replacements), $promptTemplate);

        Log::debug('Built AI prompt', [
            'debate_id' => $debate->id,
            'language' => $language,
            'template_key' => $promptTemplateKey,
            'character_limit' => $characterLimit,
            'prompt' => $prompt
        ]);

        return $prompt;
    }

    /**
     * 言語と時間に基づいて文字数/単語数制限を計算する
     *
     * @param float $timeLimitMinutes
     * @param string $language
     * @param bool $isFreeFormat フリーフォーマットかどうか
     * @return string
     */
    private function calculateCharacterLimit(float $timeLimitMinutes, string $language, bool $isFreeFormat = false): string
    {
        if ($language === 'japanese') {
            // 日本語の場合は文字数制限
            $totalChars = (int)($timeLimitMinutes * self::JAPANESE_CHARS_PER_MINUTE);
            // フリーフォーマットの場合は半分にして対話的にする
            if ($isFreeFormat) {
                $totalChars = (int)($totalChars * self::FREE_FORMAT_RESPONSE_RATIO);
            }
            return "{$totalChars}文字程度";
        } else {
            // 英語の場合は単語数制限
            $totalWords = (int)($timeLimitMinutes * self::ENGLISH_WORDS_PER_MINUTE);
            // フリーフォーマットの場合は半分にして対話的にする
            if ($isFreeFormat) {
                $totalWords = (int)($totalWords * self::FREE_FORMAT_RESPONSE_RATIO);
            }
            return "approximately {$totalWords} words";
        }
    }

    /**
     * ディベートフォーマットの詳細な説明文字列を構築する
     * 例:
     * 1. 準備時間 (5分)
     * 2. 肯定側 第一立論 (6分)
     * 3. 否定側 第一立論 (6分)
     * ...
     *
     * @param Debate $debate
     * @return string
     */
    private function buildFormatDescription(Debate $debate): string
    {
        $format = $debate->room->getDebateFormat();
        $formatDescriptionParts = [];
        $turnNumber = 1;
        // 言語に基づいてlangファイルを選択
        $language = $debate->room->language ?? 'japanese';
        // 言語に基づいたロケールを設定
        $locale = $language === 'english' ? 'en' : 'ja';

        foreach ($format as $turn) {
            $part = $turnNumber . ". "; // 番号

            // スピーカー情報を追加（肯定側/否定側）
            if (!empty($turn['speaker'])) {
                // 'affirmative' または 'negative'
                $speakerLabel = __('debates.' . $turn['speaker'], [], $locale);
                $part .= $speakerLabel . " ";
            }

            // ターン名を追加
            $part .= $turn['name'];

            // 時間を追加
            $part .= " (" . ($turn['duration'] / 60 ?? 0) . __('messages.minute_unit', [], $locale) . ")";

            // 質疑の有無を追加
            if (isset($turn['is_questions']) && $turn['is_questions']) {
                $part .= " (" . __('debates.cross_examination_available', [], $locale) . ")";
            }

            $formatDescriptionParts[] = $part;
            $turnNumber++;
        }

        return implode("\n", $formatDescriptionParts);
    }

    /**
     * エラー時や空応答時の代替メッセージを取得
     */
    private function getFallbackResponse(string $language, ?string $errorInfo = null): string
    {
        $baseMessage = __('messages.fallback_response');

        if ($errorInfo) {
            $techDetail =  __('messages.technical_issue');
            // if (config('app.debug') && $errorInfo) {
            //     $techDetail .= ': ' . $errorInfo;
            // }
            return $baseMessage . " " . $techDetail;
        }
        return $baseMessage;
    }
}
