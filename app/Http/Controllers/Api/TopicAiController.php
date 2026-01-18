<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AnalyzeTopicRequest;
use App\Http\Requests\Api\GenerateTopicRequest;
use App\Services\OpenRouter\TopicAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class TopicAiController extends Controller
{
    public function __construct(
        private TopicAiService $topicAiService
    ) {}

    /**
     * AI論題生成
     */
    public function generate(GenerateTopicRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $key = "topic_generate:{$userId}";

        // Rate Limit mainly for generation cost control
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'error' => __('topic_catalog.ai.rate_limit_exceeded'),
                'retry_after' => $seconds,
            ], 429);
        }
        RateLimiter::hit($key, 60);

        Log::info('Topic generation request', [
            'user_id' => $userId,
            'language' => $request->language,
        ]);

        $result = $this->topicAiService->generateTopics(
            $request->keywords,
            $request->category,
            $request->difficulty,
            $request->language
        );

        return $this->processResult($result, 'topics', function (&$data) {
             $data['topics'] = $this->normalizeTopics($data['topics']);
        });
    }

    /**
     * AI論題分析（インサイト）
     */
    public function insight(AnalyzeTopicRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $key = "topic_insight:{$userId}";

        if (RateLimiter::tooManyAttempts($key, 10)) {
             $seconds = RateLimiter::availableIn($key);
             return response()->json([
                 'success' => false,
                 'error' => __('topic_catalog.ai.rate_limit_exceeded'),
                 'retry_after' => $seconds,
             ], 429);
        }
        RateLimiter::hit($key, 60);

        Log::info('Topic insight request', [
            'user_id' => $userId,
            'language' => $request->language,
        ]);

        $result = $this->topicAiService->getTopicInfo(
            $request->topic, // Using 'topic' as defined in Request
            $request->language
        );

        return $this->processResult($result);
    }

    private function processResult(array $result, ?string $dataKey = null, ?callable $normalizer = null): JsonResponse
    {
        if (!$result['success']) {
            $statusCode = $result['status'] ?? 500;
            $httpStatus = $statusCode === 429 ? 429 : 422;

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? __('topic_catalog.ai.generation_failed'),
            ], $httpStatus);
        }

        if ($normalizer && isset($result[$dataKey])) {
            $normalizer($result);
        }

        return response()->json($result);
    }

    private function normalizeTopics(array $topics): array
    {
        $validCategories = ['politics', 'business', 'technology', 'education', 'philosophy', 'entertainment', 'lifestyle', 'other'];
        $validDifficulties = ['easy', 'normal', 'hard'];

        return array_values(array_filter(array_map(function ($topic) use ($validCategories, $validDifficulties) {
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
