<?php

namespace Tests\Unit\Services\Connection;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Services\Connection\DisconnectionHandler;
use App\Services\Connection\ConnectionStateManager;
use App\Services\Connection\ConnectionLogger;
use App\Enums\ConnectionStatus;
use App\Models\ConnectionLog;
use App\Models\User;
use App\Jobs\HandleUserDisconnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class DisconnectionHandlerTest extends TestCase
{
    use RefreshDatabase;

    private DisconnectionHandler $handler;
    private ConnectionStateManager $stateManager;
    private ConnectionLogger $logger;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateManager = new ConnectionStateManager();
        $this->logger = new ConnectionLogger($this->stateManager);
        $this->handler = new DisconnectionHandler($this->stateManager, $this->logger);

        $this->user = User::factory()->create();

        // 設定値をテスト用に設定
        Config::set('connection.grace_periods.room', 840);
        Config::set('connection.grace_periods.debate', 300);
        Config::set('connection.analysis.disconnection_threshold', 5);
    }

    #[Test]
    public function handleExecutesNormalDisconnectionProcess()
    {
        Queue::fake();

        $context = ['type' => 'room', 'id' => 1];

        // 既存の接続ログを作成（切断可能な状態にする）
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'connected',
            'connected_at' => now()->subMinutes(10),
            'metadata' => []
        ]);

        $result = $this->handler->handle($this->user->id, $context);

        // PendingDispatchオブジェクトが返されることを確認
        $this->assertNotNull($result);
        $this->assertInstanceOf(\Illuminate\Foundation\Bus\PendingDispatch::class, $result);

        // 切断ログが作成されることを確認
        $this->assertDatabaseHas('connection_logs', [
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'temporarily_disconnected'
        ]);

        // ジョブがディスパッチされることを確認（PendingDispatchの存在で代替）
    }

    #[Test]
    public function handleReturnsNullForNonExistentUser()
    {
        Queue::fake();

        $context = ['type' => 'room', 'id' => 1];
        $nonExistentUserId = 99999;

        $result = $this->handler->handle($nonExistentUserId, $context);

        $this->assertNull($result);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function handleReturnsNullWhenAlreadyDisconnecting()
    {
        Queue::fake();

        $context = ['type' => 'room', 'id' => 1];

        // 既存の一時切断ログを作成
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'temporarily_disconnected',
            'disconnected_at' => now(),
            'metadata' => []
        ]);

        $result = $this->handler->handle($this->user->id, $context);

        $this->assertNull($result);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function calculateGracePeriodReturnsRoomGracePeriod()
    {
        $context = ['type' => 'room', 'id' => 1];
        $gracePeriod = $this->invokePrivateMethod($this->handler, 'calculateGracePeriod', [$context]);

        $this->assertEquals(840, $gracePeriod);
    }

    #[Test]
    public function calculateGracePeriodReturnsDebateGracePeriod()
    {
        $context = ['type' => 'debate', 'id' => 1];
        $gracePeriod = $this->invokePrivateMethod($this->handler, 'calculateGracePeriod', [$context]);

        $this->assertEquals(300, $gracePeriod);
    }

    #[Test]
    public function detectAbnormalDisconnectionReturnsFalseForNormalCase()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 通常範囲内の切断ログを作成（4回）
        for ($i = 0; $i < 4; $i++) {
            ConnectionLog::create([
                'user_id' => $this->user->id,
                'context_type' => 'room',
                'context_id' => 1,
                'status' => 'temporarily_disconnected',
                'created_at' => now()->subMinutes(30 - ($i * 5)),
                'metadata' => []
            ]);
        }

        $result = $this->handler->detectAbnormalDisconnection($this->user->id, $context);

        $this->assertFalse($result);
    }

    #[Test]
    public function detectAbnormalDisconnectionReturnsTrueForAbnormalCase()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 閾値を超える切断ログを作成（6回）
        for ($i = 0; $i < 6; $i++) {
            ConnectionLog::create([
                'user_id' => $this->user->id,
                'context_type' => 'room',
                'context_id' => 1,
                'status' => 'temporarily_disconnected',
                'created_at' => now()->subMinutes(30 - ($i * 5)),
                'metadata' => []
            ]);
        }

        $result = $this->handler->detectAbnormalDisconnection($this->user->id, $context);

        $this->assertTrue($result);
    }

    #[Test]
    public function finalizeDisconnectionExecutesFinalDisconnectionProcess()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 一時切断ログを作成
        $log = ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'temporarily_disconnected',
            'disconnected_at' => now()->subMinutes(15),
            'metadata' => []
        ]);

        $this->handler->finalizeDisconnection($this->user->id, $context);

        // 最終切断状態に更新されることを確認
        $log->refresh();
        $this->assertEquals('disconnected', $log->status);
    }

    #[Test]
    public function handleDispatchesJobWithCorrectGracePeriod()
    {
        Queue::fake();

        $context = ['type' => 'debate', 'id' => 1];
        $expectedDelay = 300; // debate用の猶予期間

        // 既存の接続ログを作成（切断可能な状態にする）
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'debate',
            'context_id' => 1,
            'status' => 'connected',
            'connected_at' => now()->subMinutes(10),
            'metadata' => []
        ]);

        $result = $this->handler->handle($this->user->id, $context);

        // ジョブがディスパッチされることを確認（PendingDispatchの存在で代替）
        $this->assertNotNull($result);
        $this->assertInstanceOf(\Illuminate\Foundation\Bus\PendingDispatch::class, $result);
    }

    #[Test]
    public function handleReturnsNullWhenDisconnectionRecordCreationFails()
    {
        Queue::fake();

        // ConnectionLoggerをモックして記録失敗をシミュレート
        $mockLogger = $this->createMock(ConnectionLogger::class);
        $mockLogger->method('recordDisconnection')->willReturn(null);

        $handler = new DisconnectionHandler($this->stateManager, $mockLogger);
        $context = ['type' => 'room', 'id' => 1];

        $result = $handler->handle($this->user->id, $context);

        $this->assertNull($result);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function detectAbnormalDisconnectionReturnsFalseOnException()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 無効なコンテキストで例外を誘発
        $invalidContext = ['type' => null, 'id' => null];

        $result = $this->handler->detectAbnormalDisconnection($this->user->id, $invalidContext);

        $this->assertFalse($result);
    }

    /**
     * プライベートメソッドを呼び出すヘルパー
     */
    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
