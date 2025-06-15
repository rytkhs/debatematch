<?php

namespace Tests\Unit\Services;

use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use App\Models\DebateMessage;
use App\Services\AIEvaluationService;
use App\Services\DebateService;
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
        $this->aiEvaluationService = new AIEvaluationService($this->debateServiceMock);
    }

    // ================================
    // 基本機能テスト
    // ================================

    public function test_constructor_InitializesCorrectly()
    {
        $debateService = Mockery::mock(DebateService::class);
        $service = new AIEvaluationService($debateService);

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
                                'winner' => '肯定側',
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
                                'winner' => 'Negative',
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
                                'winner' => '肯定側',
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

        Log::shouldReceive('debug')
            ->once()
            ->with(\Mockery::type('string'));

        Log::shouldReceive('error')
            ->once()
            ->with('OpenRouter API key is not configured for evaluation.', \Mockery::type('array'));

        $result = $this->aiEvaluationService->evaluate($debate);

        $this->assertIsArray($result);
        $this->assertFalse($result['is_analyzable']);
        $this->assertStringContainsString('設定されていません', $result['reason']);
    }

    public function test_evaluate_HandlesPromptTemplateNotFound()
    {
        $this->mockConfiguration();

        // プロンプトテンプレートを明示的にnullに設定
        Config::set('ai_prompts.debate_evaluation_ja', null);
        Config::set('ai_prompts.debate_evaluation_en', null);
        Config::set('ai_prompts.debate_evaluation_free_ja', null);
        Config::set('ai_prompts.debate_evaluation_free_en', null);
        Config::set('ai_prompts.debate_evaluation_ja_no_evidence', null);
        Config::set('ai_prompts.debate_evaluation_en_no_evidence', null);

        $debate = $this->createTestDebate();

        Log::shouldReceive('debug')
            ->once()
            ->with(\Mockery::type('string'));

        Log::shouldReceive('error')
            ->once()
            ->with('AI prompt template not found in config.', \Mockery::type('array'));

        Log::shouldReceive('error')
            ->once()
            ->with('Base AI prompt template also not found.', \Mockery::type('array'));

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
            ->with('OpenRouter API Error', \Mockery::type('array'));

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
    }

    protected function mockPromptTemplates(): void
    {
        Config::set([
            'ai_prompts.debate_evaluation_ja' => 'テスト評価プロンプト: %s - %s',
            'ai_prompts.debate_evaluation_en' => 'Test evaluation prompt: %s - %s',
            'ai_prompts.debate_evaluation_free_ja' => 'フリーフォーマット評価プロンプト: %s - %s',
            'ai_prompts.debate_evaluation_free_en' => 'Free format evaluation prompt: %s - %s',
            'ai_prompts.debate_evaluation_ja_no_evidence' => 'テスト評価プロンプト（証拠なし）: %s - %s',
            'ai_prompts.debate_evaluation_en_no_evidence' => 'Test evaluation prompt (no evidence): %s - %s',
        ]);
    }
}
