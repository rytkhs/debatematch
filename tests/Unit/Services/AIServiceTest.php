<?php

namespace Tests\Unit\Services;

use App\Services\AIService;
use App\Services\DebateService;
use App\Services\OpenRouter\DebateOpponentMessageBuilder;
use App\Services\OpenRouter\OpenRouterClient;
use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use App\Models\DebateMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;

/**
 * AIServiceテスト
 *
 * TODO-027: AIService基本機能テスト
 * TODO-028: AIServiceプロンプト生成テスト
 * TODO-029: AIService外部API連携テスト
 */
class AIServiceTest extends BaseServiceTest
{
    protected AIService $aiService;
    protected MockInterface&DebateService $debateServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // DebateServiceのMockを作成
        $this->debateServiceMock = $this->createServiceMock(DebateService::class);

        // AIServiceのインスタンスを作成
        $this->aiService = $this->makeAIService();

        // ログをスパイモードで初期化
        Log::spy();
    }

    // ================================
    // TODO-027: AIService基本機能テスト
    // ================================

    public function test_constructor_InitializesDependenciesCorrectly()
    {
        // 設定値をMock
        Config::set([
            'services.openrouter.api_key' => 'test-api-key',
            'services.openrouter.model' => 'test-model',
            'services.openrouter.referer' => 'https://test.example.com',
            'services.openrouter.title' => 'Test App',
        ]);

        $service = new AIService(
            new OpenRouterClient(),
            new DebateOpponentMessageBuilder($this->debateServiceMock)
        );

        // リフレクションを使用してprivateプロパティを確認
        $reflection = new \ReflectionClass($service);

        $clientProperty = $reflection->getProperty('openRouterClient');
        $clientProperty->setAccessible(true);
        $this->assertInstanceOf(OpenRouterClient::class, $clientProperty->getValue($service));

        $builderProperty = $reflection->getProperty('messageBuilder');
        $builderProperty->setAccessible(true);
        $this->assertInstanceOf(DebateOpponentMessageBuilder::class, $builderProperty->getValue($service));
    }

    public function test_constructor_WorksWhenConfigNotSet()
    {
        // 設定をクリア（nullではなく空文字列を設定）
        Config::set([
            'services.openrouter.api_key' => '',
            'services.openrouter.model' => '',
            'services.openrouter.referer' => '',
            'services.openrouter.title' => '',
            'app.url' => 'https://default.example.com',
            'app.name' => 'Default App',
        ]);

        $service = new AIService(
            new OpenRouterClient(),
            new DebateOpponentMessageBuilder($this->debateServiceMock)
        );

        $reflection = new \ReflectionClass($service);

        $clientProperty = $reflection->getProperty('openRouterClient');
        $clientProperty->setAccessible(true);
        $this->assertInstanceOf(OpenRouterClient::class, $clientProperty->getValue($service));

        $builderProperty = $reflection->getProperty('messageBuilder');
        $builderProperty->setAccessible(true);
        $this->assertInstanceOf(DebateOpponentMessageBuilder::class, $builderProperty->getValue($service));
    }

    public function test_generateResponse_ReturnsFallbackWhenApiKeyNotConfigured()
    {
        // APIキーをクリア
        Config::set('services.openrouter.api_key', '');

        $service = new AIService(
            new OpenRouterClient(),
            new DebateOpponentMessageBuilder($this->debateServiceMock)
        );

        $debate = $this->createTestDebate();

        $response = $service->generateResponse($debate);

        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }

    public function test_generateResponse_ReturnsSuccessfulResponse()
    {
        // OpenRouter APIのMockを設定
        $this->mockOpenRouterAPI();

        // AI設定をMock
        $this->mockAIConfiguration();

        $debate = $this->createTestDebate();

        // メッセージビルダー用のフォーマット設定を準備
        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        $response = $this->aiService->generateResponse($debate);

        $this->assertIsString($response);
        $this->assertNotEmpty($response);
        $this->assertEquals('これはテスト用のAI応答です。ディベートのテーマについて詳細に論じています。', $response);
    }

    public function test_generateResponse_ReturnsEmptyContentFallback()
    {
        // 空のコンテンツを返すAPIレスポンスをMock
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => ''
                        ]
                    ]
                ]
            ], 200)
        ]);

        $this->mockAIConfiguration();

        $debate = $this->createTestDebate();

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        $response = $this->aiService->generateResponse($debate);

        // フォールバック応答をチェック
        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }

    public function test_generateResponse_HandlesApiError()
    {
        // APIエラーをMock
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([], 500)
        ]);

        $this->mockAIConfiguration();

        $debate = $this->createTestDebate();

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        $response = $this->aiService->generateResponse($debate);

        // フォールバック応答をチェック
        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }

    public function test_generateResponse_LogsRequestAndResponse()
    {
        Log::shouldReceive('debug')
            ->once()
            ->with(Mockery::type('string'), Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Received AI response successfully', Mockery::type('array'));

        $this->mockOpenRouterAPI();
        $this->mockAIConfiguration();
        $this->mockAIPromptTemplates();

        $debate = $this->createTestDebate();

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        $response = $this->aiService->generateResponse($debate);

        // レスポンスが正常に返されることを確認
        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }

    // ================================
    // TODO-029: AIService外部API連携テスト
    // ================================

    public function test_generateResponse_SendsCorrectApiRequest()
    {
        $requestCaptured = false;

        // APIリクエストの詳細をキャプチャするためのMock
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => function ($request) use (&$requestCaptured) {
                $requestCaptured = true;

                // リクエストヘッダーを検証
                $authHeader = $request->header('Authorization');
                $refererHeader = $request->header('HTTP-Referer');
                $titleHeader = $request->header('X-Title');
                $contentTypeHeader = $request->header('Content-Type');

                $this->assertEquals(['Bearer test-api-key'], $authHeader);
                $this->assertEquals(['https://test.example.com'], $refererHeader);
                $this->assertEquals(['Test App'], $titleHeader);
                $this->assertEquals(['application/json'], $contentTypeHeader);

                // リクエストボディを検証
                $body = json_decode($request->body(), true);
                $this->assertEquals('test-model', $body['model'] ?? null);
                $this->assertEquals(AIService::DEFAULT_TEMPERATURE, $body['temperature'] ?? null);
                $this->assertEquals(AIService::MAX_TOKENS, $body['max_tokens'] ?? null);

                return Http::response([
                    'choices' => [
                        [
                            'message' => [
                                'content' => 'API request validation successful'
                            ]
                        ]
                    ]
                ], 200);
            }
        ]);

        $this->mockAIConfiguration();
        $this->mockAIPromptTemplates();

        $debate = $this->createTestDebate();

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        $response = $this->aiService->generateResponse($debate);

        $this->assertTrue($requestCaptured, 'API request was not captured');
        $this->assertEquals('API request validation successful', $response);
    }

    public function test_generateResponse_SendsSystemAndUserMessages()
    {
        $requestCaptured = false;

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => function ($request) use (&$requestCaptured) {
                $requestCaptured = true;

                $body = json_decode($request->body(), true);
                $messages = $body['messages'] ?? [];
                $this->assertGreaterThanOrEqual(3, count($messages));
                $this->assertEquals('system', $messages[0]['role'] ?? null);
                $this->assertEquals('user', $messages[1]['role'] ?? null);

                $lastMessage = $messages[count($messages) - 1] ?? [];
                $this->assertEquals('user', $lastMessage['role'] ?? null);

                $this->assertNotEmpty($messages[0]['content'] ?? null);
                $this->assertNotEmpty($messages[1]['content'] ?? null);
                $this->assertStringContainsString('発言本文のみを出力してください', $lastMessage['content'] ?? '');

                return Http::response([
                    'choices' => [
                        [
                            'message' => [
                                'content' => 'API request validation successful'
                            ]
                        ]
                    ]
                ], 200);
            }
        ]);

        $this->mockAIConfiguration();
        $this->mockAIPromptTemplates();

        $debate = $this->createTestDebateWithMessages();

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        $response = $this->aiService->generateResponse($debate);

        $this->assertTrue($requestCaptured, 'API request was not captured');
        $this->assertEquals('API request validation successful', $response);
    }

    public function test_generateResponse_HandlesRateLimitError()
    {
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'error' => 'Rate limit exceeded'
            ], 429)
        ]);

        $this->mockAIConfiguration();
        $this->mockAIPromptTemplates();

        $debate = $this->createTestDebate();

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        $response = $this->aiService->generateResponse($debate);

        // フォールバック応答が返されることを確認
        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }

    public function test_generateResponse_HandlesNetworkTimeout()
    {
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            }
        ]);

        $this->mockAIConfiguration();
        $this->mockAIPromptTemplates();

        $debate = $this->createTestDebate();

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        $response = $this->aiService->generateResponse($debate);

        // フォールバック応答が返されることを確認
        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }

    public function test_generateResponse_RetriesOnConnectionException()
    {
        $this->mockAIConfiguration();
        $this->mockAIPromptTemplates();

        $debate = $this->createTestDebate();

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
                                'content' => 'リトライ成功後のAI応答',
                                'reasoning' => 'リトライ成功後の推論'
                            ]
                        ]
                    ]
                ], 200);
            }
        ]);

        // OpenRouterClient内で呼ばれる
        Log::shouldReceive('debug')
            ->once();

        // リトライが発生することを確認（現在のバグによりTypeErrorが発生する可能性がある）
        // 修正後は、リトライログが2回記録され、最終的に成功することを確認
        Log::shouldReceive('warning')
            ->times(2)
            ->with('OpenRouter API retry attempt', \Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Received AI response successfully', \Mockery::type('array'));

        $response = $this->aiService->generateResponse($debate);

        // 修正後は成功することを確認
        $this->assertIsString($response);
        $this->assertEquals('リトライ成功後のAI応答', $response);
    }

    public function test_generateResponse_HandlesMalformedApiResponse()
    {
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'invalid' => 'response structure'
            ], 200)
        ]);

        $this->mockAIConfiguration();
        $this->mockAIPromptTemplates();

        $debate = $this->createTestDebate();

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        $response = $this->aiService->generateResponse($debate);

        // フォールバック応答が返されることを確認
        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }

    public function test_generateResponse_LogsApiErrors()
    {
        Log::shouldReceive('debug');
        Log::shouldReceive('error')
            ->once()
            ->with('OpenRouter API Error after retries', Mockery::type('array'));

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'error' => 'API Error'
            ], 400)
        ]);

        $this->mockAIConfiguration();
        $this->mockAIPromptTemplates();

        $debate = $this->createTestDebate();

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        $response = $this->aiService->generateResponse($debate);

        // エラー時でもフォールバック応答が返されることを確認
        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }

    public function test_generateResponse_LogsExceptions()
    {
        Log::shouldReceive('debug')->once();
        Log::shouldReceive('error')
            ->once()
            ->with('Error generating AI response', Mockery::type('array'));

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => function () {
                throw new \Exception('Test exception');
            }
        ]);

        $this->mockAIConfiguration();
        $this->mockAIPromptTemplates();

        $debate = $this->createTestDebate();

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        $response = $this->aiService->generateResponse($debate);

        // 例外時でもフォールバック応答が返されることを確認
        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }

    public function test_getFallbackResponse_ReturnsCorrectMessage()
    {
        $reflection = new \ReflectionClass($this->aiService);
        $method = $reflection->getMethod('getFallbackResponse');
        $method->setAccessible(true);

        // 日本語メッセージ
        $jaResponse = $method->invoke($this->aiService, 'japanese');
        $this->assertIsString($jaResponse);

        // 英語メッセージ
        $enResponse = $method->invoke($this->aiService, 'english');
        $this->assertIsString($enResponse);

        // エラー情報付きメッセージ
        $errorResponse = $method->invoke($this->aiService, 'japanese', 'Test error');
        $this->assertIsString($errorResponse);
    }

    // ================================
    // ヘルパーメソッド
    // ================================

    protected function createTestDebate(): Debate
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        $room = Room::factory()->create([
            'topic' => 'テスト論題：AIの活用について',
            'language' => 'japanese',
            'status' => Room::STATUS_DEBATING,
        ]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
            'current_turn' => 1,
        ]);

        return $debate;
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

    protected function mockAIConfiguration(): void
    {
        Config::set([
            'services.openrouter.api_key' => 'test-api-key',
            'services.openrouter.model' => 'test-model',
            'services.openrouter.referer' => 'https://test.example.com',
            'services.openrouter.title' => 'Test App',
            'app.ai_user_id' => 1,
        ]);

        $this->aiService = $this->makeAIService();
    }

    protected function mockAIPromptTemplates(): void
    {
        Config::set([
            'ai_prompts.debate_ai_opponent_system_ja' => 'System prompt: {ai_side} {character_limit} {evidence_rule}',
            'ai_prompts.debate_ai_opponent_user_ja' => 'Context prompt: {resolution} {current_part_name}',
            'ai_prompts.debate_ai_opponent_system_en' => 'System prompt: {ai_side} {character_limit} {evidence_rule}',
            'ai_prompts.debate_ai_opponent_user_en' => 'Context prompt: {resolution} {current_part_name}',
            'ai_prompts.debate_ai_opponent_free_system_ja' => 'Free system prompt: {ai_side} {character_limit} {evidence_rule}',
            'ai_prompts.debate_ai_opponent_free_user_ja' => 'Free context prompt: {resolution}',
            'ai_prompts.debate_ai_opponent_free_system_en' => 'Free system prompt: {ai_side} {character_limit} {evidence_rule}',
            'ai_prompts.debate_ai_opponent_free_user_en' => 'Free context prompt: {resolution}',
            'ai_prompts.components.evidence_rule_allowed_ja' => 'Evidence Allowed JA',
            'ai_prompts.components.evidence_rule_prohibited_ja' => 'Evidence Prohibited JA',
            'ai_prompts.components.evidence_rule_allowed_en' => 'Evidence Allowed EN',
            'ai_prompts.components.evidence_rule_prohibited_en' => 'Evidence Prohibited EN',
        ]);
    }

    protected function createTestDebateWithMessages(string $language = 'japanese'): Debate
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        $room = Room::factory()->create([
            'topic' => $language === 'japanese' ? 'テスト論題：AIの活用について' : 'Test Topic: AI Utilization',
            'language' => $language,
            'status' => Room::STATUS_DEBATING,
        ]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
            'current_turn' => 1,
        ]);

        // テスト用メッセージを追加
        DebateMessage::factory()->create([
            'debate_id' => $debate->id,
            'user_id' => $affirmativeUser->id,
            'message' => $language === 'japanese' ? '肯定側の主張です。' : 'This is the affirmative argument.',
            'turn' => 1,
        ]);

        DebateMessage::factory()->create([
            'debate_id' => $debate->id,
            'user_id' => $negativeUser->id,
            'message' => $language === 'japanese' ? '否定側の反論です。' : 'This is the negative rebuttal.',
            'turn' => 2,
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
            'format_type' => 'free', // フリーフォーマット
        ]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
            'current_turn' => 1,
        ]);

        return $debate;
    }

    protected function createTestDebateWithCustomFormat(): Debate
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        $room = Room::factory()->create([
            'topic' => 'カスタムフォーマットテスト論題',
            'language' => 'japanese',
            'status' => Room::STATUS_DEBATING,
            'format_type' => 'custom',
            'custom_format_settings' => [
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
            ],
        ]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
            'current_turn' => 1,
        ]);

        return $debate;
    }

    protected function makeAIService(): AIService
    {
        return new AIService(
            new OpenRouterClient(),
            new DebateOpponentMessageBuilder($this->debateServiceMock)
        );
    }
}
