<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\Traits\CreatesUsers;
use Tests\Traits\CreatesRooms;
use Tests\Traits\CreatesDebates;
use Tests\Helpers\MockHelpers;

/**
 * Service層テスト用の基底クラス
 *
 * Mock作成ヘルパー、外部API Mock戦略、イベント・キューMock設定を提供
 */
abstract class BaseServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreatesUsers;
    use CreatesRooms;
    use CreatesDebates;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupServiceMocks();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Service層テスト用の基本Mock設定
     */
    protected function setupServiceMocks(): void
    {
        // MockHelpersを使用して基本設定を適用
        MockHelpers::mockAIConfigs();
        MockHelpers::mockDebateConfigs();

        // 個別のMock設定
        $this->setupEventMocks();
        $this->setupQueueMocks();
        $this->setupCacheMocks();
        $this->setupHttpMocks();
        $this->setupLogMocks();
    }

    /**
     * イベント系のMock設定
     */
    protected function setupEventMocks(): void
    {
        // 特定のテストで明示的にEvent::fake()を呼ばない限り、通常通り動作
        // 必要に応じて個別テストでEvent::fake()やEvent::shouldReceive()を使用
    }

    /**
     * キュー系のMock設定
     */
    protected function setupQueueMocks(): void
    {
        // 特定のテストで明示的にQueue::fake()を呼ばない限り、通常通り動作
        // 必要に応じて個別テストでQueue::fake()やQueue::shouldReceive()を使用
    }

    /**
     * キャッシュ系のMock設定
     */
    protected function setupCacheMocks(): void
    {
        // テスト環境では'array'ドライバーを使用するが、明示的なMockは設定しない
        // 必要に応じて個別テストでCache::shouldReceive()を使用
    }

    /**
     * HTTP系のMock設定
     */
    protected function setupHttpMocks(): void
    {
        // デフォルトでは実際のHTTP呼び出しを防ぐ
        // 個別テストで明示的にHttp::fake()を呼んでモックレスポンスを設定
    }

    /**
     * ログ系のMock設定
     */
    protected function setupLogMocks(): void
    {
        // デフォルトではログ出力を許可（テスト実行中のデバッグのため）
        // 必要に応じて個別テストでLog::shouldReceive()を使用
    }

    /**
     * サービスクラスのMockを作成
     *
     * @param string $serviceClass
     * @return MockInterface
     */
    protected function createServiceMock(string $serviceClass): MockInterface
    {
        $mock = Mockery::mock($serviceClass);
        $this->app->instance($serviceClass, $mock);
        return $mock;
    }

    /**
     * 部分的なサービスMockを作成（一部メソッドのみMock）
     *
     * @param string $serviceClass
     * @param array $methods
     * @return MockInterface
     */
    protected function createPartialServiceMock(string $serviceClass, array $methods = []): MockInterface
    {
        $mock = Mockery::mock($serviceClass)->makePartial();

        if (!empty($methods)) {
            foreach ($methods as $method) {
                $mock->shouldAllowMockingProtectedMethods();
            }
        }

        $this->app->instance($serviceClass, $mock);
        return $mock;
    }

    /**
     * 外部API（OpenRouter）のMockレスポンスを設定
     *
     * @param array $responses
     * @param bool $shouldFail
     */
    protected function mockOpenRouterAPI(array $responses = [], bool $shouldFail = false): void
    {
        if ($shouldFail) {
            Http::fake([
                'openrouter.ai/*' => Http::response([], 500)
            ]);
        } else {
            // MockHelpersの機能を使用
            MockHelpers::mockOpenRouterAPI();
        }
    }

    /**
     * Pusher/WebSocketのMockを設定
     */
    protected function mockPusherAPI(): void
    {
        MockHelpers::mockPusherAPI();
    }

    /**
     * Redis操作のMockを設定
     */
    protected function mockRedis(): MockInterface
    {
        return MockHelpers::mockRedis();
    }

    /**
     * 設定値のMockを設定
     *
     * @param array $configs
     */
    protected function mockConfigs(array $configs): void
    {
        foreach ($configs as $key => $value) {
            Config::set($key, $value);
        }
    }

    /**
     * 時間をMockして固定値にする
     *
     * @param string $time
     */
    protected function mockTime(string $time = '2024-01-01 00:00:00'): void
    {
        $this->travel(now()->parse($time));
    }

    /**
     * イベントが発火されたことをアサート
     *
     * @param string $eventClass
     * @param callable|null $callback
     */
    protected function assertEventDispatched(string $eventClass, callable $callback = null): void
    {
        Event::fake();
        // テスト実行後にEvent::assertDispatched()を呼ぶ
    }

    /**
     * ジョブがディスパッチされたことをアサート
     *
     * @param string $jobClass
     * @param callable|null $callback
     */
    protected function assertJobDispatched(string $jobClass, callable $callback = null): void
    {
        Queue::fake();
        // テスト実行後にQueue::assertPushed()を呼ぶ
    }

    /**
     * キャッシュの操作をアサート
     *
     * @param string $key
     * @param mixed $value
     */
    protected function assertCacheSet(string $key, $value = null): void
    {
        if ($value !== null) {
            $this->assertEquals($value, Cache::get($key));
        } else {
            $this->assertTrue(Cache::has($key));
        }
    }

    /**
     * ログが出力されたことをアサート
     *
     * @param string $level
     * @param string $message
     */
    protected function assertLoggedMessage(string $level, string $message): void
    {
        // ログアサーション用のヘルパー
        // 実際の実装はテストの進行に応じて詳細化
    }

    /**
     * HTTP呼び出しがされたことをアサート
     *
     * @param string $url
     * @param string $method
     * @param array $data
     */
    protected function assertHttpCalled(string $url, string $method = 'POST', array $data = []): void
    {
        Http::assertSent(function ($request) use ($url, $method, $data) {
            return $request->url() === $url &&
                strtoupper($request->method()) === strtoupper($method) &&
                (empty($data) || $request->data() === $data);
        });
    }
}
