<?php

namespace App\Services;

use App\Models\Debate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Throwable; // Throwable を use

class AIService
{
    protected string $apiKey;
    protected string $model;
    protected string $referer;
    protected string $title;
    protected DebateService $debateService;

    public function __construct(DebateService $debateService)
    {
        $this->apiKey = Config::get('services.openrouter.api_key');
        $this->model = Config::get('services.openrouter.model', 'google/gemini-pro');
        $this->referer = Config::get('services.openrouter.referer', config('app.url'));
        $this->title = Config::get('services.openrouter.title', config('app.name'));
        $this->debateService = $debateService;
    }

    /**
     * ディベートの状況に基づいてAIの応答を生成する
     *
     * @param Debate $debate
     * @return string AIの応答メッセージ
     * @throws \Exception APIエラーやプロンプト生成エラーが発生した場合
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
            ])->post('https://openrouter.ai/api/v1/chat/completions', [
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
     */
    protected function buildPrompt(Debate $debate): string
    {
        $room = $debate->room;
        $language = $room->language ?? 'japanese';
        $topic = $room->topic;
        // AIユーザーIDを定数または設定ファイルから取得
        $aiUserId = (int)config('app.ai_user_id', 9);

        // AIのサイドを決定
        $aiSide = ($debate->affirmative_user_id === $aiUserId) ? 'affirmative' : 'negative';
        $userSide = ($aiSide === 'affirmative') ? 'negative' : 'affirmative';

        // ディベートフォーマットと現在のターン情報を取得
        $format = $this->debateService->getFormat($debate);
        $currentTurnNumber = $debate->current_turn;
        $currentTurnInfo = $format[$currentTurnNumber] ?? null;

        if (!$currentTurnInfo) {
            throw new \Exception("Could not get current turn info for debate {$debate->id}, turn {$currentTurnNumber}");
        }
        $currentTurnName = $currentTurnInfo['name'];

        // ディベート履歴を整形
        $history = $debate->messages()
            ->with('user')
            ->orderBy('created_at')
            ->get()
            ->map(function ($msg) use ($debate, $language, $aiUserId, $format) {
                $turnName = $format[$msg->turn]['name'] ?? 'Unknown Turn';
                $speakerSide = ($msg->user_id === $debate->affirmative_user_id) ? 'affirmative' : 'negative';
                $speakerLabel = '';
                if ($language === 'japanese') {
                    $speakerLabel = ($speakerSide === 'affirmative' ? '肯定側' : '否定側');
                    if ($msg->user_id === $aiUserId) $speakerLabel .= "(あなた)";
                } else {
                    $speakerLabel = ($speakerSide === 'affirmative' ? 'Affirmative' : 'Negative');
                     if ($msg->user_id === $aiUserId) $speakerLabel .= "(You)";
                }
                return "[{$turnName}] {$speakerLabel}: {$msg->message}";
            })
            ->implode("\n");

        // 言語に応じた指示を生成
        $instructions = $this->getInstructions($language, $aiSide, $topic, $currentTurnName);

        // プロンプト全体を組み立て
        $prompt = <<<PROMPT
{$instructions}

# ディベート履歴
{$history}

# あなたの現在の発言パート
{$currentTurnName}

# 指示
上記のディベート履歴と現在のパートを踏まえ、あなたの立場で反駁、質疑、または立論の続きを行ってください。論理的で、簡潔かつ的確な発言を生成してください。
あなたの質疑のパートの場合、質問は1つずつ行い、質問の必要がなくなるか、制限時間まで繰り返してください。
PROMPT;

        // 英語の場合
        if ($language === 'english') {
            $prompt = <<<PROMPT
{$instructions}

# Debate History
{$history}

# Your Current Speaking Speech
{$currentTurnName}

# Instruction
Based on the debate history and your current speech, please provide a rebuttal, ask a question, or continue your constructive argument from your assigned position. Generate a logical, concise, and precise statement.
If this is your cross examination, ask one question at a time and continue asking as needed until no further questions are necessary or until the time limit is reached.
PROMPT;
        }


        return $prompt;
    }

    /**
     * 言語とサイドに基づいて指示文を生成するヘルパー関数
     */
    protected function getInstructions(string $language, string $aiSide, string $topic, string $currentTurnName): string
    {
        if ($language === 'japanese') {
            $sideName = ($aiSide === 'affirmative') ? '肯定側' : '否定側';
            return <<<TEXT
あなたはディベート参加者です。以下の設定でディベートに参加しています。
論題: {$topic}
あなたの立場: {$sideName}
証拠資料の使用の有無: 使用しない
現在のパート: {$currentTurnName}
あなたは指定された立場で、論題に対して説得力のある主張を行う必要があります。
TEXT;
        } else {
            $sideName = ($aiSide === 'affirmative') ? 'Affirmative' : 'Negative';
            return <<<TEXT
You are a debate participant. You are participating in a debate with the following setup:
Topic: {$topic}
Your Side: {$sideName}
Evidence Usage: Not used
Current Speech: {$currentTurnName}
You must make persuasive arguments for your assigned stance on the topic.
TEXT;
        }
    }

    /**
     * エラー時や空応答時の代替メッセージを取得
     */
    protected function getFallbackResponse(string $language, ?string $errorInfo = null): string
    {
        $baseMessage = ($language === 'japanese')
            ? "申し訳ありません、現在応答を生成できません。"
            : "Sorry, I cannot generate a response at the moment.";

        if ($errorInfo) {
             $techDetail = ($language === 'japanese') ? "(技術的な問題が発生しました)" : "(A technical issue occurred)";
             // $techDetail .= $errorInfo;
             return $baseMessage . " " . $techDetail;
        }
        return $baseMessage;
    }
}
