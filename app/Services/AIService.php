<?php

namespace App\Services;

use App\Models\Debate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Throwable;

class AIService
{
    protected string $apiKey;
    protected string $model;
    protected string $referer;
    protected string $title;
    protected DebateService $debateService;
    protected int $aiUserId;

    // 言語別の1分あたりの文字/単語数定数
    const JAPANESE_CHARS_PER_MINUTE = 320;
    const ENGLISH_WORDS_PER_MINUTE = 160;

    public function __construct(DebateService $debateService)
    {
        $this->apiKey = Config::get('services.openrouter.api_key');
        $this->model = Config::get('services.openrouter.model', 'google/gemini-pro');
        $this->referer = Config::get('services.openrouter.referer', config('app.url'));
        $this->title = Config::get('services.openrouter.title', config('app.name'));
        $this->debateService = $debateService;
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
                ->timeout(240)
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 7000,
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
    protected function buildPrompt(Debate $debate): string
    {
        $room = $debate->room;
        $language = $room->language ?? 'japanese';
        $topic = $room->topic;

        $aiRawSide = ($debate->affirmative_user_id === $this->aiUserId) ? 'affirmative' : 'negative';

        // ディベートフォーマットと現在のターン情報を取得
        $format = $this->debateService->getFormat($debate);
        $currentTurnNumber = $debate->current_turn;
        $currentTurnInfo = $format[$currentTurnNumber] ?? null;

        if (!$currentTurnInfo) {
            Log::error("Could not get current turn info for debate {$debate->id}, turn {$currentTurnNumber}");
            throw new \Exception("Could not get current turn info for debate {$debate->id}, turn {$currentTurnNumber}");
        }

        $currentTurnName = $currentTurnInfo['name'];
        $timeLimitMinutes = $currentTurnInfo['duration'] / 60 ?? 180;

        // 言語に応じた文字数/単語数制限の計算
        $characterLimit = $this->calculateCharacterLimit($timeLimitMinutes, $language);

        // ディベート履歴を整形
        $history = $debate->messages()
            ->with('user')
            ->orderBy('created_at')
            ->get()
            ->map(function ($msg) use ($debate, $language, $format) {
                $turnName = $format[$msg->turn]['name'] ?? 'unknown speech';
                $speakerSide = ($msg->user_id === $debate->affirmative_user_id) ? 'affirmative' : 'negative';
                $speakerLabel = '';

                if ($language === 'japanese') {
                    $speakerLabel = ($speakerSide === 'affirmative' ? '肯定側' : '否定側');
                } else {
                    $speakerLabel = ($speakerSide === 'affirmative' ? 'Affirmative Side' : 'Negative Side');
                }
                // 履歴として整形
                $messageContent = nl2br(e($msg->message)); // HTMLエスケープと改行の保持
                return "[{$turnName}] {$speakerLabel}:\n{$messageContent}"; // メッセージ内容を改行後に表示
            })
            ->implode("\n\n");

        // 言語に応じたプロンプトテンプレートを取得
        $promptTemplateKey = ($language === 'japanese') ? 'ai_prompts.debate_ai_opponent_ja' : 'ai_prompts.debate_ai_opponent_en';
        $promptTemplate = Config::get($promptTemplateKey);

        if (!$promptTemplate) {
            Log::error("AI opponent prompt template not found for language: {$language}");
            throw new \Exception("AI opponent prompt template not configured for language: {$language}");
        }

        $aiSideName = ($language === 'japanese')
            ? (($aiRawSide === 'affirmative') ? '肯定側' : '否定側')
            : (($aiRawSide === 'affirmative') ? 'Affirmative' : 'Negative');

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
     * @return string
     */
    protected function calculateCharacterLimit(float $timeLimitMinutes, string $language): string
    {
        if ($language === 'japanese') {
            // 日本語の場合は文字数制限
            $totalChars = (int)($timeLimitMinutes * self::JAPANESE_CHARS_PER_MINUTE);
            return "{$totalChars}文字程度";
        } else {
            // 英語の場合は単語数制限
            $totalWords = (int)($timeLimitMinutes * self::ENGLISH_WORDS_PER_MINUTE);
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
    protected function buildFormatDescription(Debate $debate): string
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
    protected function getFallbackResponse(string $language, ?string $errorInfo = null): string
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
