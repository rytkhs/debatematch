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
    protected MockInterface $debateServiceMock;

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

        $this->aiService->generateResponse($debate);
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

        // プロンプトテンプレートを設定しない
        Config::set('ai_prompts.debate_ai_opponent_ja', null);

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
