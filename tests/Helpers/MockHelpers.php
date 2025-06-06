<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * テスト用のMockヘルパークラス
 *
 * 外部サービスやLaravelファサードのMock設定を簡素化
 */
class MockHelpers
{
    /**
     * OpenRouter API用のMock設定
     */
    public static function mockOpenRouterAPI(): array
    {
        $responses = [
            'success' => [
                'choices' => [
                    [
                        'message' => [
                            'content' => 'これはテスト用のAI応答です。ディベートのテーマについて詳細に論じています。'
                        ]
                    ]
                ]
            ],
            'empty_content' => [
                'choices' => [
                    [
                        'message' => [
                            'content' => ''
                        ]
                    ]
                ]
            ],
            'malformed' => [
                'error' => 'Invalid request format'
            ],
            'rate_limit' => [
                'error' => 'Rate limit exceeded'
            ]
        ];

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => function ($request) use ($responses) {
                // リクエスト内容に基づいて適切なレスポンスを返す
                $body = $request->body();
                $data = json_decode($body, true);

                // 特定の条件でエラーレスポンスを返す
                if (
                    isset($data['messages'][0]['content']) &&
                    str_contains($data['messages'][0]['content'], 'ERROR_TRIGGER')
                ) {
                    return Http::response($responses['malformed'], 400);
                }

                if (
                    isset($data['messages'][0]['content']) &&
                    str_contains($data['messages'][0]['content'], 'RATE_LIMIT_TRIGGER')
                ) {
                    return Http::response($responses['rate_limit'], 429);
                }

                if (
                    isset($data['messages'][0]['content']) &&
                    str_contains($data['messages'][0]['content'], 'EMPTY_CONTENT_TRIGGER')
                ) {
                    return Http::response($responses['empty_content'], 200);
                }

                return Http::response($responses['success'], 200);
            }
        ]);

        return $responses;
    }

    /**
     * Pusher/Broadcasting Mock設定
     */
    public static function mockPusherAPI(): void
    {
        // Pusher設定をテスト用に変更
        Config::set([
            'broadcasting.connections.pusher.key' => 'test-pusher-key',
            'broadcasting.connections.pusher.secret' => 'test-pusher-secret',
            'broadcasting.connections.pusher.app_id' => 'test-pusher-app-id',
            'broadcasting.connections.pusher.options.cluster' => 'test-cluster',
            'broadcasting.connections.pusher.options.host' => 'test-host',
            'broadcasting.connections.pusher.options.port' => 443,
            'broadcasting.connections.pusher.options.scheme' => 'https',
            'broadcasting.connections.pusher.options.encrypted' => true,
        ]);

        // 実際のPusher呼び出しをMock
        Http::fake([
            'api.pusherapp.com/*' => Http::response(['ok' => true], 200)
        ]);
    }

    /**
     * Redis Mock設定
     */
    public static function mockRedis(): \Mockery\MockInterface
    {
        $redisMock = Mockery::mock('Illuminate\Redis\RedisManager');

        // 基本的なRedis操作をMock
        $redisMock->shouldReceive('get')->andReturn(null);
        $redisMock->shouldReceive('set')->andReturn(true);
        $redisMock->shouldReceive('del')->andReturn(1);
        $redisMock->shouldReceive('exists')->andReturn(false);
        $redisMock->shouldReceive('expire')->andReturn(true);
        $redisMock->shouldReceive('incr')->andReturn(1);
        $redisMock->shouldReceive('decr')->andReturn(0);

        // その他のメソッドも基本対応
        $redisMock->shouldIgnoreMissing();

        app()->instance('redis', $redisMock);

        return $redisMock;
    }

    /**
     * Event Mock設定（よく使われるイベント用）
     */
    public static function mockLaravelEvents(): void
    {
        Event::fake([
            \App\Events\TurnAdvanced::class,
            \App\Events\DebateFinished::class,
            \App\Events\DebateTerminated::class,
            \App\Events\EarlyTerminationRequested::class,
            \App\Events\EarlyTerminationAgreed::class,
            \App\Events\EarlyTerminationDeclined::class,
        ]);
    }

    /**
     * Queue Mock設定（よく使われるジョブ用）
     */
    public static function mockLaravelQueue(): void
    {
        Queue::fake([
            \App\Jobs\AdvanceDebateTurnJob::class,
            \App\Jobs\EvaluateDebateJob::class,
            \App\Jobs\GenerateAIResponseJob::class,
            \App\Jobs\EarlyTerminationTimeoutJob::class,
            \App\Jobs\HandleUserDisconnection::class,
        ]);
    }

    /**
     * Cache Mock設定
     */
    public static function mockCache(): \Mockery\MockInterface
    {
        $cacheMock = Mockery::mock('Illuminate\Cache\CacheManager');

        // 基本的なキャッシュ操作をMock
        $cacheMock->shouldReceive('get')->andReturn(null);
        $cacheMock->shouldReceive('put')->andReturn(true);
        $cacheMock->shouldReceive('forget')->andReturn(true);
        $cacheMock->shouldReceive('has')->andReturn(false);
        $cacheMock->shouldReceive('remember')->andReturnUsing(function ($key, $minutes, $callback) {
            return $callback();
        });

        // その他の方法を基本対応
        $cacheMock->shouldIgnoreMissing();

        app()->instance('cache', $cacheMock);

        return $cacheMock;
    }

    /**
     * Log Mock設定
     */
    public static function mockLog(): \Mockery\MockInterface
    {
        $logMock = Mockery::mock('Illuminate\Log\LogManager');

        // すべてのログレベルをMock
        $logMock->shouldReceive('debug')->andReturn(null);
        $logMock->shouldReceive('info')->andReturn(null);
        $logMock->shouldReceive('notice')->andReturn(null);
        $logMock->shouldReceive('warning')->andReturn(null);
        $logMock->shouldReceive('error')->andReturn(null);
        $logMock->shouldReceive('critical')->andReturn(null);
        $logMock->shouldReceive('alert')->andReturn(null);
        $logMock->shouldReceive('emergency')->andReturn(null);

        app()->instance('log', $logMock);

        return $logMock;
    }

    /**
     * Slack通知Mock設定
     */
    public static function mockSlackNotifications(): void
    {
        Http::fake([
            'hooks.slack.com/*' => Http::response(['ok' => true], 200)
        ]);
    }

    /**
     * 外部API全体のMock設定（統合）
     */
    public static function mockAllExternalAPIs(): array
    {
        $mocks = [
            'openrouter' => self::mockOpenRouterAPI(),
        ];

        self::mockPusherAPI();
        self::mockSlackNotifications();

        return $mocks;
    }

    /**
     * Laravel内部サービス全体のMock設定
     */
    public static function mockAllLaravelServices(): array
    {
        $mocks = [
            'redis' => self::mockRedis(),
            'cache' => self::mockCache(),
            'log' => self::mockLog(),
        ];

        self::mockLaravelEvents();
        self::mockLaravelQueue();

        return $mocks;
    }

    /**
     * Service層テスト用の包括的Mock設定
     *
     * @param array $options Mock設定のオプション
     * @return array 作成されたMockインスタンス
     */
    public static function setupServiceTestMocks(array $options = []): array
    {
        $defaultOptions = [
            'mock_external_apis' => true,
            'mock_laravel_services' => true,
            'mock_time' => false,
            'time' => '2024-01-01 00:00:00',
        ];

        $options = array_merge($defaultOptions, $options);
        $mocks = [];

        if ($options['mock_external_apis']) {
            $mocks['external'] = self::mockAllExternalAPIs();
        }

        if ($options['mock_laravel_services']) {
            $mocks['laravel'] = self::mockAllLaravelServices();
        }

        if ($options['mock_time']) {
            self::mockTime($options['time']);
        }

        return $mocks;
    }

    /**
     * 時間のMock設定
     */
    public static function mockTime(string $time = '2024-01-01 00:00:00'): void
    {
        // Carbon::setTestNow() を使用して時間を固定
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse($time));
    }

    /**
     * 時間のMockをリセット
     */
    public static function resetTimeMock(): void
    {
        \Carbon\Carbon::setTestNow();
    }

    /**
     * 設定値のMock
     */
    public static function mockConfigs(array $configs): void
    {
        foreach ($configs as $key => $value) {
            Config::set($key, $value);
        }
    }

    /**
     * テスト用のAI設定を適用
     */
    public static function mockAIConfigs(): void
    {
        self::mockConfigs([
            'services.openrouter.api_key' => 'test-api-key',
            'services.openrouter.model' => 'test-model',
            'services.openrouter.referer' => 'http://test.local',
            'services.openrouter.title' => 'Test App',
            'app.ai_user_id' => 1,
        ]);
    }

    /**
     * テスト用のディベート設定を適用
     */
    public static function mockDebateConfigs(): void
    {
        self::mockConfigs([
            'app.ai_user_id' => 1,
            'debate.turn_timeout' => 600, // 10分
            'debate.preparation_time' => 300, // 5分
            'debate.early_termination_timeout' => 300, // 5分
        ]);
    }

    /**
     * すべてのMockをリセット
     */
    public static function resetAllMocks(): void
    {
        Http::fake([]); // HTTPモックをリセット
        Event::fake([]); // イベントモックをリセット
        Queue::fake([]); // キューモックをリセット
        self::resetTimeMock();
        Mockery::close();
    }
}
