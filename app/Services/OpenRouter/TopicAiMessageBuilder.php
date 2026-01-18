<?php

namespace App\Services\OpenRouter;

use Illuminate\Support\Facades\Config;

/**
 * AIによる論題生成用のメッセージビルダー
 */
class TopicAiMessageBuilder
{
    /**
     * リクエストタイプに応じたペイロードを構築
     */
    public function build(string $type, array $params): array
    {
        return match ($type) {
            'generate' => $this->buildGeneratePayload($params),
            'info' => $this->buildInfoPayload($params),
            default => throw new \InvalidArgumentException("Unknown type: {$type}"),
        };
    }

    /**
     * 新規論題生成用のペイロードを構築
     */
    private function buildGeneratePayload(array $params): array
    {
        $language = $params['language'] ?? 'japanese';
        $keywords = $params['keywords'] ?? '';
        $category = $params['category'] ?? null;
        $difficulty = $params['difficulty'] ?? null;

        $systemPrompt = $this->getGenerateSystemPrompt($language);
        $userPrompt = $this->getGenerateUserPrompt($language, $keywords, $category, $difficulty);

        return [
            'model' => $this->getModel('generate'),
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'response_format' => ['type' => 'json_object'],
        ];
    }

    /**
     * 論題背景情報取得用のペイロードを構築
     */
    private function buildInfoPayload(array $params): array
    {
        $language = $params['language'] ?? 'japanese';
        $topic = $params['topic'] ?? '';

        $systemPrompt = $this->getInfoSystemPrompt($language);
        $userPrompt = $this->getInfoUserPrompt($language, $topic);

        return [
            'model' => $this->getModel('info'),
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'response_format' => ['type' => 'json_object'],
        ];
    }

    private function getModel(string $type): string
    {
        $configKey = $type === 'generate'
            ? 'services.openrouter.topic_generation_model'
            : 'services.openrouter.topic_insight_model';

        return Config::get($configKey, 'qwen/qwen-2.5-7b-instruct');
    }

    // ========================================
    // Generate Prompts
    // ========================================

    private function getGenerateSystemPrompt(string $language): string
    {
        if ($language === 'english') {
            return <<<'PROMPT'
You are an expert in competitive debate topics. Your task is to generate debate topics (resolutions) that are well-balanced, thought-provoking, and suitable for competitive debate.

## Requirements for Topics
- Topics must be debatable with clear affirmative and negative positions
- Topics should be phrased as policy propositions (e.g., "The government should...", "X is better than Y", "Society should...")
- Topics must be specific enough to debate but broad enough for meaningful discussion
- Avoid topics that are too trivial or too complex for general debate

## Output Format (JSON)
Strictly return a valid JSON object in this format:
{
  "topics": [
    {"text": "Topic 1 text", "category": "category_key", "difficulty": "difficulty_key"},
    {"text": "Topic 2 text", "category": "category_key", "difficulty": "difficulty_key"},
    {"text": "Topic 3 text", "category": "category_key", "difficulty": "difficulty_key"},
    {"text": "Topic 4 text", "category": "category_key", "difficulty": "difficulty_key"}
  ]
}

Valid category_key: politics, business, technology, education, philosophy, entertainment, lifestyle, other
Valid difficulty_key: easy, normal, hard
PROMPT;
        }

        return <<<'PROMPT'
あなたは競技ディベートの論題専門家です。バランスの取れた、思考を促す、競技ディベートに適した論題を生成することがあなたの任務です。

## 論題の要件
- 肯定側と否定側の両方の立場が明確に取れる、議論可能なテーマであること
- 政策命題の形式で表現されること（例：「政府は〜すべきである」「〜は〜よりも優れている」「社会は〜すべきである」）
- 議論可能なほど具体的でありながら、有意義な議論ができる程度に幅広いこと
- ディベートに適さないほど些細なテーマや、複雑すぎるテーマは避けること

## 出力形式（JSON）
必ず以下の形式の有効なJSONオブジェクトを返してください：
{
  "topics": [
    {"text": "論題1のテキスト", "category": "カテゴリキー", "difficulty": "難易度キー"},
    {"text": "論題2のテキスト", "category": "カテゴリキー", "difficulty": "難易度キー"},
    {"text": "論題3のテキスト", "category": "カテゴリキー", "difficulty": "難易度キー"},
    {"text": "論題4のテキスト", "category": "カテゴリキー", "difficulty": "難易度キー"}
  ]
}

有効なcategory_key: politics, business, technology, education, philosophy, entertainment, lifestyle, other
有効なdifficulty_key: easy, normal, hard
PROMPT;
    }

    private function getGenerateUserPrompt(string $language, string $keywords, ?string $category, ?string $difficulty): string
    {
        $categoryLabel = $this->getCategoryLabel($category, $language);
        $difficultyLabel = $this->getDifficultyLabel($difficulty, $language);

        if ($language === 'english') {
            $parts = ["Please generate 4 debate topics with the following conditions:"];

            if (!empty($keywords)) {
                $parts[] = "- Keywords/Theme: {$keywords}";
            } else {
                $parts[] = "- Keywords/Theme: General topics (choose diverse and interesting themes)";
            }

            if ($category && $category !== 'all') {
                $parts[] = "- Category: {$categoryLabel}";
            }

            if ($difficulty && $difficulty !== 'all') {
                $parts[] = "- Difficulty: {$difficultyLabel}";
            }

            $parts[] = "\nGenerate topics in English.";

            return implode("\n", $parts);
        }

        $parts = ["以下の条件でディベート論題を4つ生成してください："];

        if (!empty($keywords)) {
            $parts[] = "- キーワード/テーマ: {$keywords}";
        } else {
            $parts[] = "- キーワード/テーマ: 一般的なトピック（多様で興味深いテーマを選んでください）";
        }

        if ($category && $category !== 'all') {
            $parts[] = "- カテゴリ: {$categoryLabel}";
        }

        if ($difficulty && $difficulty !== 'all') {
            $parts[] = "- 難易度: {$difficultyLabel}";
        }

        $parts[] = "\n論題は日本語で生成してください。";

        return implode("\n", $parts);
    }

    // ========================================
    // Info Prompts
    // ========================================

    private function getInfoSystemPrompt(string $language): string
    {
        if ($language === 'english') {
            return <<<'PROMPT'
You are an expert in competitive debate. Your task is to provide background information and key arguments for debate topics to help debaters prepare.

## Output Format (JSON)
{
  "info": {
    "topic": "The debate topic",
    "description": "Brief explanation of the topic and relevant background",
    "key_points": {
      "affirmative": ["Point 1 for affirmative", "Point 2 for affirmative", "Point 3 for affirmative"],
      "negative": ["Point 1 for negative", "Point 2 for negative", "Point 3 for negative"]
    }
  }
}
PROMPT;
        }

        return <<<'PROMPT'
あなたは競技ディベートの専門家です。ディベーターの準備を助けるために、論題の背景情報と主要な論点を提供することがあなたの任務です。

## 出力形式（JSON）
{
  "info": {
    "topic": "ディベート論題",
    "description": "論題の簡単な説明と関連する背景情報",
    "key_points": {
      "affirmative": ["肯定側の論点1", "肯定側の論点2", "肯定側の論点3"],
      "negative": ["否定側の論点1", "否定側の論点2", "否定側の論点3"]
    }
  }
}
PROMPT;
    }

    private function getInfoUserPrompt(string $language, string $topic): string
    {
        if ($language === 'english') {
            return "Please provide background information and key arguments for the following debate topic:\n\nTopic: {$topic}";
        }

        return "以下のディベート論題について、背景情報と主要な論点を提供してください：\n\n論題: {$topic}";
    }

    // ========================================
    // Helper Methods
    // ========================================

    private function getCategoryLabel(?string $category, string $language): string
    {
        if (!$category) {
            return '';
        }

        $labels = [
            'politics' => ['ja' => '政治・社会', 'en' => 'Politics & Society'],
            'business' => ['ja' => 'ビジネス・経済', 'en' => 'Business & Economy'],
            'technology' => ['ja' => 'テクノロジー・科学', 'en' => 'Technology & Science'],
            'education' => ['ja' => '教育・学校', 'en' => 'Education'],
            'philosophy' => ['ja' => '倫理・哲学', 'en' => 'Ethics & Philosophy'],
            'entertainment' => ['ja' => 'エンタメ・趣味', 'en' => 'Entertainment'],
            'lifestyle' => ['ja' => 'ライフスタイル・恋愛', 'en' => 'Lifestyle'],
            'other' => ['ja' => 'その他', 'en' => 'Other'],
        ];

        $key = $language === 'english' ? 'en' : 'ja';
        return $labels[$category][$key] ?? $category;
    }

    private function getDifficultyLabel(?string $difficulty, string $language): string
    {
        if (!$difficulty) {
            return '';
        }

        $labels = [
            'easy' => ['ja' => 'Easy（初級）', 'en' => 'Easy (Beginner)'],
            'normal' => ['ja' => 'Normal（中級）', 'en' => 'Normal (Intermediate)'],
            'hard' => ['ja' => 'Hard（上級）', 'en' => 'Hard (Advanced)'],
        ];

        $key = $language === 'english' ? 'en' : 'ja';
        return $labels[$difficulty][$key] ?? $difficulty;
    }
}
