<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenRouter\TopicGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class TopicSuggestionController extends Controller
{
    public function __construct(
        private TopicGeneratorService $topicGeneratorService
    ) {}

    /**
     * AI論題生成エンドポイント
     */
    public function generate(Request $request): JsonResponse
    {
        // レートリミット（1ユーザーあたり10回/分）
        $userId = auth()->id();
        $key = "topic_generate:{$userId}";

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'error' => __('topic_catalog.ai.rate_limit_exceeded'),
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($key, 60);

        // バリデーション
        $validated = $request->validate([
            'type' => 'required|in:generate,info',
            'keywords' => 'nullable|string|max:200',
            'category' => 'nullable|string|in:all,politics,business,technology,education,philosophy,entertainment,lifestyle,other',
            'difficulty' => 'nullable|string|in:all,easy,normal,hard',
            'base_topic' => 'required_if:type,info|nullable|string|max:500',
            'language' => 'required|in:japanese,english',
        ]);

        $type = $validated['type'];
        $language = $validated['language'];

        // キーワードのサニタイズ（空白トリム、制御文字除去）
        $keywords = isset($validated['keywords'])
            ? $this->sanitizeInput($validated['keywords'])
            : null;

        // base_topicのサニタイズ
        $baseTopic = isset($validated['base_topic'])
            ? $this->sanitizeInput($validated['base_topic'])
            : null;

        Log::info('Topic suggestion request', [
            'user_id' => $userId,
            'type' => $type,
            'language' => $language,
        ]);

        try {
            $result = match ($type) {
                'generate' => $this->topicGeneratorService->generateTopics(
                    $keywords,
                    $validated['category'] ?? null,
                    $validated['difficulty'] ?? null,
                    $language
                ),
                'info' => $this->topicGeneratorService->getTopicInfo(
                    $baseTopic,
                    $language
                ),
            };

            if (!$result['success']) {
                // ステータスコードがあればそれを使用、なければ500
                $statusCode = $result['status'] ?? 500;
                // 429はそのまま、それ以外は422（処理失敗）
                $httpStatus = $statusCode === 429 ? 429 : 422;

                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? __('topic_catalog.ai.generation_failed'),
                ], $httpStatus);
            }

            // 成功レスポンスでtopicsが存在する場合、各トピックを検証・正規化
            if (isset($result['topics'])) {
                $result['topics'] = $this->normalizeTopics($result['topics']);
            }

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('Topic suggestion controller error', [
                'exception' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => false,
                'error' => __('topic_catalog.ai.generation_failed'),
            ], 500);
        }
    }

    /**
     * 入力文字列をサニタイズ
     */
    private function sanitizeInput(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // 制御文字を除去（改行・タブは許可）
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);

        // 前後の空白をトリム
        $sanitized = trim($sanitized);

        // 空文字列はnullに変換
        return $sanitized === '' ? null : $sanitized;
    }

    /**
     * AI生成されたトピックを検証・正規化
     */
    private function normalizeTopics(array $topics): array
    {
        $validCategories = ['politics', 'business', 'technology', 'education', 'philosophy', 'entertainment', 'lifestyle', 'other'];
        $validDifficulties = ['easy', 'normal', 'hard'];

        return array_values(array_filter(array_map(function ($topic) use ($validCategories, $validDifficulties) {
            // textが必須
            if (!isset($topic['text']) || !is_string($topic['text']) || trim($topic['text']) === '') {
                return null;
            }

            return [
                'text' => trim($topic['text']),
                'category' => in_array($topic['category'] ?? null, $validCategories, true)
                    ? $topic['category']
                    : null,
                'difficulty' => in_array($topic['difficulty'] ?? null, $validDifficulties, true)
                    ? $topic['difficulty']
                    : null,
            ];
        }, $topics)));
    }
}
