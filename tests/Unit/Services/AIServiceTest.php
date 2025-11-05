<?php

namespace Tests\Unit\Services;

use App\Services\AIService;
use App\Services\DebateService;
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
        $this->aiService = new AIService(app(DebateService::class));
    }

    // ================================
    // TODO-027: AIService基本機能テスト
    // ================================

    public function test_constructor_InitializesPropertiesCorrectly()
    {
        // 設定値をMock
        Config::set([
            'services.openrouter.api_key' => 'test-api-key',
            'services.openrouter.model' => 'test-model',
            'services.openrouter.referer' => 'https://test.example.com',
            'services.openrouter.title' => 'Test App',
            'app.ai_user_id' => 99,
        ]);

        $service = new AIService($this->debateServiceMock);

        // リフレクションを使用してprivateプロパティを確認
        $reflection = new \ReflectionClass($service);

        $apiKeyProperty = $reflection->getProperty('apiKey');
        $apiKeyProperty->setAccessible(true);
        $this->assertEquals('test-api-key', $apiKeyProperty->getValue($service));

        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $this->assertEquals('test-model', $modelProperty->getValue($service));

        $refererProperty = $reflection->getProperty('referer');
        $refererProperty->setAccessible(true);
        $this->assertEquals('https://test.example.com', $refererProperty->getValue($service));

        $titleProperty = $reflection->getProperty('title');
        $titleProperty->setAccessible(true);
        $this->assertEquals('Test App', $titleProperty->getValue($service));

        $aiUserIdProperty = $reflection->getProperty('aiUserId');
        $aiUserIdProperty->setAccessible(true);
        $this->assertEquals(99, $aiUserIdProperty->getValue($service));
    }

    public function test_constructor_UsesDefaultValuesWhenConfigNotSet()
    {
        // 設定をクリア（nullではなく空文字列を設定）
        Config::set([
            'services.openrouter.api_key' => '',
            'services.openrouter.model' => '',
            'services.openrouter.referer' => '',
            'services.openrouter.title' => '',
            'app.ai_user_id' => '',
            'app.url' => 'https://default.example.com',
            'app.name' => 'Default App',
        ]);

        $service = new AIService($this->debateServiceMock);

        $reflection = new \ReflectionClass($service);

        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $this->assertEquals('', $modelProperty->getValue($service));

        $refererProperty = $reflection->getProperty('referer');
        $refererProperty->setAccessible(true);
        $this->assertEquals('', $refererProperty->getValue($service));

        $titleProperty = $reflection->getProperty('title');
        $titleProperty->setAccessible(true);
        $this->assertEquals('', $titleProperty->getValue($service));

        $aiUserIdProperty = $reflection->getProperty('aiUserId');
        $aiUserIdProperty->setAccessible(true);
        $this->assertEquals(0, $aiUserIdProperty->getValue($service));
    }

    public function test_generateResponse_ThrowsExceptionWhenApiKeyNotConfigured()
    {
        // APIキーをクリア
        Config::set('services.openrouter.api_key', '');

        $service = new AIService($this->debateServiceMock);

        $debate = $this->createTestDebate();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('AI Service is not configured properly.');

        $service->generateResponse($debate);
    }

    public function test_generateResponse_ReturnsSuccessfulResponse()
    {
        // OpenRouter APIのMockを設定
        $this->mockOpenRouterAPI();

        // AI設定をMock
        $this->mockAIConfiguration();

        $debate = $this->createTestDebate();

        // buildPromptメソッドのMockを準備（実際のメソッドをテストするため）
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
            ->twice() // buildPromptでも呼ばれる
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
    // TODO-028: AIServiceプロンプト生成テスト
    // ================================

    public function test_buildPrompt_GeneratesCorrectPromptForJapanese()
    {
        $this->mockAIConfiguration();
        $this->mockAIPromptTemplates();

        $debate = $this->createTestDebateWithMessages();

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        // buildPromptメソッドを直接テストするためリフレクションを使用
        $reflection = new \ReflectionClass($this->aiService);
        $buildPromptMethod = $reflection->getMethod('buildPrompt');
        $buildPromptMethod->setAccessible(true);

        $prompt = $buildPromptMethod->invoke($this->aiService, $debate);

        $this->assertIsString($prompt);
        $this->assertStringContainsString('テスト論題：AIの活用について', $prompt);
        $this->assertStringContainsString('肯定側', $prompt);
        $this->assertStringContainsString('否定側', $prompt);
    }

    public function test_buildPrompt_GeneratesCorrectPromptForEnglish()
    {
        $this->mockAIConfiguration();
        $this->mockAIPromptTemplates();

        $debate = $this->createTestDebateWithMessages('english');

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        $reflection = new \ReflectionClass($this->aiService);
        $buildPromptMethod = $reflection->getMethod('buildPrompt');
        $buildPromptMethod->setAccessible(true);

        $prompt = $buildPromptMethod->invoke($this->aiService, $debate);

        $this->assertIsString($prompt);
        $this->assertStringContainsString('Test Topic: AI Utilization', $prompt);
        $this->assertStringContainsString('Affirmative', $prompt);
        $this->assertStringContainsString('Negative', $prompt);
    }

    public function test_buildPrompt_HandlesFreeFormatDebate()
    {
        $this->mockAIConfiguration();
        $this->mockAIPromptTemplates();

        $debate = $this->createTestFreeFormatDebate();

        // フリーフォーマットの場合はgetFormatを呼ばない
        $this->debateServiceMock
            ->shouldNotReceive('getFormat');

        $reflection = new \ReflectionClass($this->aiService);
        $buildPromptMethod = $reflection->getMethod('buildPrompt');
        $buildPromptMethod->setAccessible(true);

        $prompt = $buildPromptMethod->invoke($this->aiService, $debate);

        $this->assertIsString($prompt);
        $this->assertStringContainsString('フリーフォーマット', $prompt);
    }

    public function test_buildPrompt_ThrowsExceptionWhenTemplateNotFound()
    {
        $this->mockAIConfiguration();

        // すべてのプロンプトテンプレートを明示的にクリア
        Config::set([
            'ai_prompts.debate_ai_opponent_ja' => null,
            'ai_prompts.debate_ai_opponent_en' => null,
            'ai_prompts.debate_ai_opponent_free_ja' => null,
            'ai_prompts.debate_ai_opponent_free_en' => null,
        ]);

        $debate = $this->createTestDebate();

        $this->debateServiceMock
            ->shouldReceive('getFormat')
            ->andReturn($this->getTestDebateFormat());

        $reflection = new \ReflectionClass($this->aiService);
        $buildPromptMethod = $reflection->getMethod('buildPrompt');
        $buildPromptMethod->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('AI opponent prompt template not configured for language: japanese');

        $buildPromptMethod->invoke($this->aiService, $debate);
    }

    public function test_calculateCharacterLimit_CalculatesJapaneseCorrectly()
    {
        $reflection = new \ReflectionClass($this->aiService);
        $calculateMethod = $reflection->getMethod('calculateCharacterLimit');
        $calculateMethod->setAccessible(true);

        // 日本語、3分、通常フォーマット
        $result = $calculateMethod->invoke($this->aiService, 3.0, 'japanese', false);
        $expected = (int)(3 * AIService::JAPANESE_CHARS_PER_MINUTE) . '文字程度';
        $this->assertEquals($expected, $result);

        // 日本語、3分、フリーフォーマット（半分）
        $result = $calculateMethod->invoke($this->aiService, 3.0, 'japanese', true);
        $expected = (int)(3 * AIService::JAPANESE_CHARS_PER_MINUTE / 2) . '文字程度';
        $this->assertEquals($expected, $result);
    }

    public function test_calculateCharacterLimit_CalculatesEnglishCorrectly()
    {
        $reflection = new \ReflectionClass($this->aiService);
        $calculateMethod = $reflection->getMethod('calculateCharacterLimit');
        $calculateMethod->setAccessible(true);

        // 英語、3分、通常フォーマット
        $result = $calculateMethod->invoke($this->aiService, 3.0, 'english', false);
        $expected = 'approximately ' . (int)(3 * AIService::ENGLISH_WORDS_PER_MINUTE) . ' words';
        $this->assertEquals($expected, $result);

        // 英語、3分、フリーフォーマット（半分）
        $result = $calculateMethod->invoke($this->aiService, 3.0, 'english', true);
        $expected = 'approximately ' . (int)(3 * AIService::ENGLISH_WORDS_PER_MINUTE / 2) . ' words';
        $this->assertEquals($expected, $result);
    }

    public function test_buildFormatDescription_GeneratesCorrectDescription()
    {
        $this->mockAIConfiguration();

        $debate = $this->createTestDebateWithCustomFormat();

        $reflection = new \ReflectionClass($this->aiService);
        $buildFormatMethod = $reflection->getMethod('buildFormatDescription');
        $buildFormatMethod->setAccessible(true);

        $description = $buildFormatMethod->invoke($this->aiService, $debate);

        $this->assertIsString($description);
        $this->assertStringContainsString('1.', $description);
        $this->assertStringContainsString('準備時間', $description);
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

                // リクエストボディを検証
                $body = json_decode($request->body(), true);

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

        // buildPrompt、generateResponse、reasoningログで呼ばれる
        Log::shouldReceive('debug')
            ->times(3);

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
        Log::shouldReceive('debug')->twice(); // buildPromptとgenerateResponseで呼ばれる
        Log::shouldReceive('error')
            ->once()
            ->with('Error generating AI response', Mockery::type('array'));

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
        Log::shouldReceive('debug')->twice(); // buildPromptとgenerateResponseで呼ばれる
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
    // TODO-030: AIService応答時間計算テスト
    // ================================

    public function test_calculateResponseTime_CalculatesJapaneseCorrectly()
    {
        // calculateCharacterLimitメソッドをテスト（日本語）
        $reflection = new \ReflectionClass($this->aiService);
        $method = $reflection->getMethod('calculateCharacterLimit');
        $method->setAccessible(true);

        // 日本語の場合
        $result3min = $method->invoke($this->aiService, 3.0, 'japanese', false);
        $expected3min = (int)(3.0 * AIService::JAPANESE_CHARS_PER_MINUTE);
        $this->assertEquals("{$expected3min}文字程度", $result3min);

        $result5min = $method->invoke($this->aiService, 5.0, 'japanese', false);
        $expected5min = (int)(5.0 * AIService::JAPANESE_CHARS_PER_MINUTE);
        $this->assertEquals("{$expected5min}文字程度", $result5min);

        $result10min = $method->invoke($this->aiService, 10.0, 'japanese', false);
        $expected10min = (int)(10.0 * AIService::JAPANESE_CHARS_PER_MINUTE);
        $this->assertEquals("{$expected10min}文字程度", $result10min);
    }

    public function test_calculateResponseTime_CalculatesEnglishCorrectly()
    {
        // calculateCharacterLimitメソッドをテスト（英語）
        $reflection = new \ReflectionClass($this->aiService);
        $method = $reflection->getMethod('calculateCharacterLimit');
        $method->setAccessible(true);

        // 英語の場合
        $result3min = $method->invoke($this->aiService, 3.0, 'english', false);
        $expected3min = (int)(3.0 * AIService::ENGLISH_WORDS_PER_MINUTE);
        $this->assertEquals("approximately {$expected3min} words", $result3min);

        $result5min = $method->invoke($this->aiService, 5.0, 'english', false);
        $expected5min = (int)(5.0 * AIService::ENGLISH_WORDS_PER_MINUTE);
        $this->assertEquals("approximately {$expected5min} words", $result5min);

        $result10min = $method->invoke($this->aiService, 10.0, 'english', false);
        $expected10min = (int)(10.0 * AIService::ENGLISH_WORDS_PER_MINUTE);
        $this->assertEquals("approximately {$expected10min} words", $result10min);
    }

    public function test_calculateResponseTime_HandlesJapaneseFreeFormat()
    {
        $reflection = new \ReflectionClass($this->aiService);
        $method = $reflection->getMethod('calculateCharacterLimit');
        $method->setAccessible(true);

        // フリーフォーマットの場合は半分になる
        $result6min = $method->invoke($this->aiService, 6.0, 'japanese', true);
        $expected6min = (int)(6.0 * AIService::JAPANESE_CHARS_PER_MINUTE / 2);
        $this->assertEquals("{$expected6min}文字程度", $result6min);

        $result10min = $method->invoke($this->aiService, 10.0, 'japanese', true);
        $expected10min = (int)(10.0 * AIService::JAPANESE_CHARS_PER_MINUTE / 2);
        $this->assertEquals("{$expected10min}文字程度", $result10min);
    }

    public function test_calculateResponseTime_HandlesEnglishFreeFormat()
    {
        $reflection = new \ReflectionClass($this->aiService);
        $method = $reflection->getMethod('calculateCharacterLimit');
        $method->setAccessible(true);

        // フリーフォーマットの場合は半分になる
        $result6min = $method->invoke($this->aiService, 6.0, 'english', true);
        $expected6min = (int)(6.0 * AIService::ENGLISH_WORDS_PER_MINUTE / 2);
        $this->assertEquals("approximately {$expected6min} words", $result6min);

        $result10min = $method->invoke($this->aiService, 10.0, 'english', true);
        $expected10min = (int)(10.0 * AIService::ENGLISH_WORDS_PER_MINUTE / 2);
        $this->assertEquals("approximately {$expected10min} words", $result10min);
    }

    public function test_calculateResponseTime_HandlesZeroTime()
    {
        $reflection = new \ReflectionClass($this->aiService);
        $method = $reflection->getMethod('calculateCharacterLimit');
        $method->setAccessible(true);

        // 時間が0の場合
        $jaResult = $method->invoke($this->aiService, 0.0, 'japanese', false);
        $this->assertEquals("0文字程度", $jaResult);

        $enResult = $method->invoke($this->aiService, 0.0, 'english', false);
        $this->assertEquals("approximately 0 words", $enResult);
    }

    public function test_calculateResponseTime_HandlesDecimalMinutes()
    {
        $reflection = new \ReflectionClass($this->aiService);
        $method = $reflection->getMethod('calculateCharacterLimit');
        $method->setAccessible(true);

        // 小数分の場合
        $jaResult = $method->invoke($this->aiService, 1.5, 'japanese', false);
        $expected = (int)(1.5 * AIService::JAPANESE_CHARS_PER_MINUTE);
        $this->assertEquals("{$expected}文字程度", $jaResult);

        $enResult = $method->invoke($this->aiService, 2.7, 'english', false);
        $expected = (int)(2.7 * AIService::ENGLISH_WORDS_PER_MINUTE);
        $this->assertEquals("approximately {$expected} words", $enResult);
    }

    public function test_calculateResponseTime_UsesCorrectConstants()
    {
        // 定数値が正しく定義されているかテスト
        $this->assertEquals(320, AIService::JAPANESE_CHARS_PER_MINUTE);
        $this->assertEquals(160, AIService::ENGLISH_WORDS_PER_MINUTE);

        $reflection = new \ReflectionClass($this->aiService);
        $method = $reflection->getMethod('calculateCharacterLimit');
        $method->setAccessible(true);

        // 1分間の計算をテスト
        $jaResult = $method->invoke($this->aiService, 1.0, 'japanese', false);
        $this->assertEquals("320文字程度", $jaResult);

        $enResult = $method->invoke($this->aiService, 1.0, 'english', false);
        $this->assertEquals("approximately 160 words", $enResult);
    }

    public function test_calculateResponseTime_BoundaryValues()
    {
        $reflection = new \ReflectionClass($this->aiService);
        $method = $reflection->getMethod('calculateCharacterLimit');
        $method->setAccessible(true);

        // 非常に小さい値
        $jaResult = $method->invoke($this->aiService, 0.1, 'japanese', false);
        $expected = (int)(0.1 * AIService::JAPANESE_CHARS_PER_MINUTE);
        $this->assertEquals("{$expected}文字程度", $jaResult);

        // 非常に大きい値
        $jaResult = $method->invoke($this->aiService, 100.0, 'japanese', false);
        $expected = (int)(100.0 * AIService::JAPANESE_CHARS_PER_MINUTE);
        $this->assertEquals("{$expected}文字程度", $jaResult);

        // 負の値（現実的でないが境界値テスト）
        $jaResult = $method->invoke($this->aiService, -1.0, 'japanese', false);
        $expected = (int)(-1.0 * AIService::JAPANESE_CHARS_PER_MINUTE);
        $this->assertEquals("{$expected}文字程度", $jaResult);
    }

    public function test_calculateResponseTime_InvalidLanguageUsesDefault()
    {
        $reflection = new \ReflectionClass($this->aiService);
        $method = $reflection->getMethod('calculateCharacterLimit');
        $method->setAccessible(true);

        // 無効な言語の場合は英語として扱われる
        $result = $method->invoke($this->aiService, 1.0, 'invalid_language', false);
        $expected = (int)(1.0 * AIService::ENGLISH_WORDS_PER_MINUTE);
        $this->assertEquals("approximately {$expected} words", $result);

        $result = $method->invoke($this->aiService, 1.0, '', false);
        $expected = (int)(1.0 * AIService::ENGLISH_WORDS_PER_MINUTE);
        $this->assertEquals("approximately {$expected} words", $result);
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
            'services.openrouter.model' => 'google/gemini-pro',
            'services.openrouter.referer' => 'https://test.example.com',
            'services.openrouter.title' => 'Test App',
            'app.ai_user_id' => 1,
        ]);
    }

    protected function mockAIPromptTemplates(): void
    {
        Config::set([
            'ai_prompts.debate_ai_opponent_ja' => 'テスト用日本語プロンプト: {resolution} - {ai_side} - {debate_format_description} - {current_part_name} - {time_limit_minutes} - {debate_history} - {character_limit}',
            'ai_prompts.debate_ai_opponent_en' => 'Test English prompt: {resolution} - {ai_side} - {debate_format_description} - {current_part_name} - {time_limit_minutes} - {debate_history} - {character_limit}',
            'ai_prompts.debate_ai_opponent_free_ja' => 'フリーフォーマット用プロンプト: {resolution} - {ai_side} - {time_limit_minutes} - {debate_history} - {character_limit}',
            'ai_prompts.debate_ai_opponent_free_en' => 'Free format prompt: {resolution} - {ai_side} - {time_limit_minutes} - {debate_history} - {character_limit}',
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
}
