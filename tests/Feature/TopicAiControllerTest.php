<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TopicAiControllerTest extends TestCase
{
    public function test_insight_rejects_topic_that_sanitizes_to_null(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        RateLimiter::clear("topic_insight:{$user->id}");

        $response = $this->postJson(route('api.ai.topics.insight'), [
            'topic' => "\u{0001}",
            'language' => 'japanese',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => __('topic_catalog.ai.base_topic_required'),
        ]);
    }
}
