<?php

namespace Tests\Unit\Services;

use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use App\Models\DebateMessage;
use App\Services\AIEvaluationService;
use App\Services\DebateService;
use App\Services\OpenRouter\DebateEvaluationMessageBuilder;
use App\Services\OpenRouter\OpenRouterClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;

class AIEvaluationServiceTest extends BaseServiceTest
{
    protected $aiEvaluationService;
    protected $debateServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // DebateServiceのモックを作成
        $this->debateServiceMock = Mockery::mock(DebateService::class);

        // AIEvaluationServiceを初期化
        $this->aiEvaluationService = $this->makeAIEvaluationService();
    }

    // ================================
    // 基本機能テスト
    // ================================

    public function test_constructor_InitializesCorrectly()
    {
        $debateService = Mockery::mock(DebateService::class);
        $service = new AIEvaluationService(
            new OpenRouterClient(),
            new DebateEvaluationMessageBuilder($debateService)
        );

        $this->assertInstanceOf(AIEvaluationService::class, $service);
    }

    public function test_evaluate_ReturnsSuccessfulEvaluation()
    {
        $this->mockConfiguration();
        $this->mockPromptTemplates();

        $debate = $this->createTestDebate();
        $this->createTestMessages($debate);

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'isAnalyzable' => true,
                                'analysis' => 'テスト分析結果',
                                'reason' => 'テスト判定理由',
                                'winner' => 'affirmative',
                                'feedbackForAffirmative' => 'テスト肯定側フィードバック',
                                'feedbackForNegative' => 'テスト否定側フィードバック'
                            ])
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->aiEvaluationService->evaluate($debate);

        $this->assertIsArray($result);
        $this->assertTrue($result['is_analyzable']);
        $this->assertEquals('affirmative', $result['winner']);
        $this->assertEquals('テスト分析結果', $result['analysis']);
        $this->assertEquals('テスト判定理由', $result['reason']);
        $this->assertEquals('テスト肯定側フィードバック', $result['feedback_for_affirmative']);
        $this->assertEquals('テスト否定側フィードバック', $result['feedback_for_negative']);
    }

    public function test_evaluate_HandlesEnglishLanguage()
    {
        $this->mockConfiguration();
        $this->mockPromptTemplates();

        $debate = $this->createTestDebate('english');
        $this->createTestMessages($debate);

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'isAnalyzable' => true,
                                'analysis' => 'Test analysis result',
                                'reason' => 'Test judgment reason',
                                'winner' => 'negative',
                                'feedbackForAffirmative' => 'Test affirmative feedback',
                                'feedbackForNegative' => 'Test negative feedback'
                            ])
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->aiEvaluationService->evaluate($debate);

        $this->assertIsArray($result);
        $this->assertTrue($result['is_analyzable']);
        $this->assertEquals('negative', $result['winner']);
        $this->assertEquals('Test analysis result', $result['analysis']);
    }

    public function test_evaluate_HandlesFreeFormatDebate()
    {
        $this->mockConfiguration();
        $this->mockPromptTemplates();

        $debate = $this->createTestFreeFormatDebate();
        $this->createTestMessages($debate);

        // フリーフォーマットの場合はgetFormatを呼ばない可能性がある
        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'isAnalyzable' => true,
                                'analysis' => 'フリーフォーマット分析',
                                'reason' => 'フリーフォーマット判定',
                                'winner' => 'affirmative',
                                'feedbackForAffirmative' => 'フリーフォーマットフィードバック1',
                                'feedbackForNegative' => 'フリーフォーマットフィードバック2'
                            ])
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->aiEvaluationService->evaluate($debate);

        $this->assertIsArray($result);
        $this->assertTrue($result['is_analyzable']);
        $this->assertEquals('affirmative', $result['winner']);
    }

    public function test_evaluate_HandlesNotAnalyzableResponse()
    {
        $this->mockConfiguration();
        $this->mockPromptTemplates();

        $debate = $this->createTestDebate();
        $this->createTestMessages($debate);

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'isAnalyzable' => false,
                                'analysis' => null,
                                'reason' => null,
                                'winner' => null,
                                'feedbackForAffirmative' => null,
                                'feedbackForNegative' => null
                            ])
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $this->aiEvaluationService->evaluate($debate);

        $this->assertIsArray($result);
        $this->assertFalse($result['is_analyzable']);
        $this->assertNull($result['winner']);
        $this->assertEquals('評価できませんでした', $result['analysis']);
        $this->assertEquals('評価できませんでした', $result['reason']);
    }

    // ================================
    // エラーハンドリングテスト
    // ================================

    public function test_evaluate_HandlesApiKeyNotConfigured()
    {
        $this->mockPromptTemplates();

        // APIキーを明示的にnullに設定
        Config::set('services.openrouter.api_key', null);
        Config::set('services.openrouter.evaluation_model', 'test-model');
        Config::set('app.url', 'https://test.example.com');

        $debate = $this->createTestDebate();

        Log::shouldReceive('error')
            ->once()
            ->with('Error generating AI evaluation', \Mockery::type('array'));

        $this->aiEvaluationService = $this->makeAIEvaluationService();

        $result = $this->aiEvaluationService->evaluate($debate);

        $this->assertIsArray($result);
        $this->assertFalse($result['is_analyzable']);
        $this->assertStringContainsString('設定されていません', $result['reason']);
    }

    public function test_evaluate_HandlesPromptTemplateNotFound()
    {
        $this->mockConfiguration();

        // プロンプトテンプレートを明示的にnullに設定
        Config::set('ai_prompts.debate_evaluation_system_ja', null);
        Config::set('ai_prompts.debate_evaluation_user_ja', null);
        Config::set('ai_prompts.debate_evaluation_system_en', null);
        Config::set('ai_prompts.debate_evaluation_user_en', null);
        Config::set('ai_prompts.debate_evaluation_free_system_ja', null);
        Config::set('ai_prompts.debate_evaluation_free_user_ja', null);
        Config::set('ai_prompts.debate_evaluation_free_system_en', null);
        Config::set('ai_prompts.debate_evaluation_free_user_en', null);
        Config::set('ai_prompts.debate_evaluation_system_ja_no_evidence', null);
        Config::set('ai_prompts.debate_evaluation_user_ja_no_evidence', null);
        Config::set('ai_prompts.debate_evaluation_system_en_no_evidence', null);
        Config::set('ai_prompts.debate_evaluation_user_en_no_evidence', null);

        $debate = $this->createTestDebate();

        Log::shouldReceive('error')
            ->once()
            ->with('Error generating AI evaluation', \Mockery::type('array'));

        $result = $this->aiEvaluationService->evaluate($debate);

        $this->assertIsArray($result);
        $this->assertFalse($result['is_analyzable']);
        $this->assertStringContainsString('プロンプトテンプレートが見つかりません', $result['reason']);
    }

    public function test_evaluate_HandlesApiError()
    {
        $this->mockConfiguration();
        $this->mockPromptTemplates();

        $debate = $this->createTestDebate();
        $this->createTestMessages($debate);

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'error' => 'API Error'
            ], 500)
        ]);

        Log::shouldReceive('debug')
            ->once()
            ->with(\Mockery::type('string'));

        Log::shouldReceive('error')
            ->once()
            ->with('OpenRouter API Error after retries (evaluation)', \Mockery::type('array'));

        $result = $this->aiEvaluationService->evaluate($debate);

        $this->assertIsArray($result);
        $this->assertFalse($result['is_analyzable']);
        $this->assertStringContainsString('通信に失敗しました', $result['reason']);
    }

    public function test_evaluate_HandlesMalformedJsonResponse()
    {
        $this->mockConfiguration();
        $this->mockPromptTemplates();

        $debate = $this->createTestDebate();
        $this->createTestMessages($debate);

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'invalid json content'
                        ]
                    ]
                ]
            ], 200)
        ]);

        Log::shouldReceive('debug')
            ->once()
            ->with(\Mockery::type('string'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to parse AI response JSON', \Mockery::type('array'));

        $result = $this->aiEvaluationService->evaluate($debate);

        $this->assertIsArray($result);
        $this->assertFalse($result['is_analyzable']);
        $this->assertStringContainsString('解析に失敗しました', $result['reason']);
    }

    public function test_evaluate_RetriesOnConnectionException()
    {
        $this->mockConfiguration();
        $this->mockPromptTemplates();

        $debate = $this->createTestDebate();
        $this->createTestMessages($debate);

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        // 最初の2回はConnectionExceptionを発生させ、3回目で成功レスポンスを返す
        $attemptCount = 0;
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => function () use (&$attemptCount) {
                $attemptCount++;
                if ($attemptCount < 3) {
                    throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
                }
                // 3回目で成功レスポンスを返す
                return Http::response([
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode([
                                    'isAnalyzable' => true,
                                    'analysis' => 'リトライ成功後の分析',
                                    'reason' => 'リトライ成功後の判定理由',
                                    'winner' => 'affirmative',
                                    'feedbackForAffirmative' => 'リトライ成功後の肯定側フィードバック',
                                    'feedbackForNegative' => 'リトライ成功後の否定側フィードバック'
                                ])
                            ]
                        ]
                    ]
                ], 200);
            }
        ]);

        Log::shouldReceive('debug')
            ->once()
            ->with(\Mockery::type('string'));

        // リトライが発生することを確認（現在のバグによりTypeErrorが発生する可能性がある）
        // 修正後は、リトライログが2回記録され、最終的に成功することを確認
        Log::shouldReceive('warning')
            ->times(2)
            ->with('OpenRouter API retry attempt (evaluation)', \Mockery::type('array'));

        $result = $this->aiEvaluationService->evaluate($debate);

        // 修正後は成功することを確認
        $this->assertIsArray($result);
        $this->assertTrue($result['is_analyzable']);
        $this->assertEquals('affirmative', $result['winner']);
        $this->assertEquals('リトライ成功後の分析', $result['analysis']);
    }

    // ================================
    // ヘルパーメソッド
    // ================================

    protected function createTestDebate(string $language = 'japanese'): Debate
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        $room = Room::factory()->create([
            'topic' => $language === 'japanese' ? 'テスト論題：AIの活用について' : 'Test Topic: AI Utilization',
            'language' => $language,
            'status' => Room::STATUS_DEBATING,
            'evidence_allowed' => true,
        ]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
            'current_turn' => 1,
        ]);

        return $debate;
    }

    protected function createTestFreeFormatDebate(): Debate
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        $room = Room::factory()->create([
            'topic' => 'フリーフォーマットテスト論題',
            'language' => 'japanese',
            'status' => Room::STATUS_DEBATING,
            'format_type' => 'free',
        ]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
            'current_turn' => 1,
        ]);

        return $debate;
    }

    protected function createTestMessages(Debate $debate): void
    {
        // 肯定側メッセージ
        DebateMessage::factory()->create([
            'debate_id' => $debate->id,
            'user_id' => $debate->affirmative_user_id,
            'message' => '肯定側の立論です。',
            'turn' => 1,
        ]);

        // 否定側メッセージ
        DebateMessage::factory()->create([
            'debate_id' => $debate->id,
            'user_id' => $debate->negative_user_id,
            'message' => '否定側の反駁です。',
            'turn' => 2,
        ]);
    }

    protected function getTestDebateFormat(): array
    {
        return [
            0 => [
                'name' => '準備時間',
                'speaker' => null,
                'duration' => 300,
                'is_questions' => false,
            ],
            1 => [
                'name' => '肯定側第一立論',
                'speaker' => 'affirmative',
                'duration' => 360,
                'is_questions' => false,
            ],
            2 => [
                'name' => '否定側第一立論',
                'speaker' => 'negative',
                'duration' => 360,
                'is_questions' => false,
            ],
        ];
    }

    protected function mockConfiguration(bool $withApiKey = true): void
    {
        $config = [
            'services.openrouter.evaluation_model' => 'test-evaluation-model',
            'app.url' => 'https://test.example.com',
        ];

        if ($withApiKey) {
            $config['services.openrouter.api_key'] = 'test-api-key';
        }

        Config::set($config);

        $this->aiEvaluationService = $this->makeAIEvaluationService();
    }

    protected function makeAIEvaluationService(): AIEvaluationService
    {
        return new AIEvaluationService(
            new OpenRouterClient(),
            new DebateEvaluationMessageBuilder($this->debateServiceMock)
        );
    }

    protected function mockPromptTemplates(): void
    {
        Config::set([
            'ai_prompts.debate_evaluation_system_ja' => 'System prompt: {resolution}',
            'ai_prompts.debate_evaluation_user_ja' => 'User prompt: {transcript_block}',
            'ai_prompts.debate_evaluation_system_en' => 'System prompt: {resolution}',
            'ai_prompts.debate_evaluation_user_en' => 'User prompt: {transcript_block}',
            'ai_prompts.debate_evaluation_system_ja_no_evidence' => 'System prompt (no evidence): {resolution}',
            'ai_prompts.debate_evaluation_user_ja_no_evidence' => 'User prompt (no evidence): {transcript_block}',
            'ai_prompts.debate_evaluation_system_en_no_evidence' => 'System prompt (no evidence): {resolution}',
            'ai_prompts.debate_evaluation_user_en_no_evidence' => 'User prompt (no evidence): {transcript_block}',
            'ai_prompts.debate_evaluation_free_system_ja' => 'Free system prompt: {resolution}',
            'ai_prompts.debate_evaluation_free_user_ja' => 'Free user prompt: {transcript_block}',
            'ai_prompts.debate_evaluation_free_system_en' => 'Free system prompt: {resolution}',
            'ai_prompts.debate_evaluation_free_user_en' => 'Free user prompt: {transcript_block}',
        ]);
    }
}
