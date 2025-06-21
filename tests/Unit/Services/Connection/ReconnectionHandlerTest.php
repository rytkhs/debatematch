<?php

namespace Tests\Unit\Services\Connection;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Services\Connection\ReconnectionHandler;
use App\Services\Connection\ConnectionStateManager;
use App\Services\Connection\ConnectionLogger;
use App\Models\ConnectionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class ReconnectionHandlerTest extends TestCase
{
    use RefreshDatabase;

    private ReconnectionHandler $handler;
    private ConnectionStateManager $stateManager;
    private ConnectionLogger $logger;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateManager = new ConnectionStateManager();
        $this->logger = new ConnectionLogger($this->stateManager);
        $this->handler = new ReconnectionHandler($this->stateManager, $this->logger);

        $this->user = User::factory()->create();

        // 設定値をテスト用に設定
        Config::set('connection.grace_periods.room', 840);
        Config::set('connection.grace_periods.debate', 300);
    }

    #[Test]
    public function handleExecutesNormalReconnectionProcess()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 一時切断ログを作成
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'temporarily_disconnected',
            'disconnected_at' => now()->subMinutes(5),
            'metadata' => []
        ]);

        $result = $this->handler->handle($this->user->id, $context);

        $this->assertTrue($result);

        // ログが再接続状態に更新されることを確認
        $this->assertDatabaseHas('connection_logs', [
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'connected'
        ]);
    }

    #[Test]
    public function handleReturnsFalseForNonExistentUser()
    {
        $context = ['type' => 'room', 'id' => 1];
        $nonExistentUserId = 99999;

        $result = $this->handler->handle($nonExistentUserId, $context);

        $this->assertFalse($result);
    }

    #[Test]
    public function handleReturnsFalseWhenAlreadyConnected()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 既存の接続中ログを作成
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'connected',
            'connected_at' => now()->subMinutes(10),
            'metadata' => []
        ]);

        $result = $this->handler->handle($this->user->id, $context);

        $this->assertFalse($result);
    }

    #[Test]
    public function handleProcessesAsNewConnection()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 接続ログが存在しない場合
        $result = $this->handler->handle($this->user->id, $context);

        $this->assertTrue($result);

        // 新規接続ログが作成されることを確認
        $this->assertDatabaseHas('connection_logs', [
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'connected'
        ]);
    }

    #[Test]
    public function validateReconnectionAllowsValidReconnection()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 一時切断ログを作成
        $log = ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'temporarily_disconnected',
            'disconnected_at' => now()->subMinutes(5),
            'metadata' => []
        ]);

        $isValid = $this->invokePrivateMethod($this->handler, 'validateReconnection', [$log, $context]);

        $this->assertTrue($isValid);
    }

    #[Test]
    public function validateReconnectionRejectsInvalidState()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 最終切断ログを作成
        $log = ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'disconnected',
            'disconnected_at' => now()->subMinutes(30),
            'metadata' => []
        ]);

        $isValid = $this->invokePrivateMethod($this->handler, 'validateReconnection', [$log, $context]);

        $this->assertFalse($isValid);
    }

    #[Test]
    public function isReconnectionInProgressDetectsRecentReconnection()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 最近の再接続ログを作成
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'connected',
            'reconnected_at' => now(),
            'metadata' => []
        ]);

        $inProgress = $this->invokePrivateMethod($this->handler, 'isReconnectionInProgress', [$this->user->id, $context]);

        $this->assertTrue($inProgress);
    }

    #[Test]
    public function isReconnectionInProgressIgnoresOldReconnection()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 古い再接続ログを作成
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'connected',
            'reconnected_at' => now()->subMinutes(2),
            'metadata' => []
        ]);

        $inProgress = $this->invokePrivateMethod($this->handler, 'isReconnectionInProgress', [$this->user->id, $context]);

        $this->assertFalse($inProgress);
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
    public function buildReconnectionMetadataBuildsAppropriateMetadata()
    {
        $log = ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'temporarily_disconnected',
            'disconnected_at' => now()->subMinutes(5),
            'metadata' => []
        ]);

        $additionalMetadata = ['custom_field' => 'test_value'];

        $metadata = $this->handler->buildReconnectionMetadata($log, $additionalMetadata);

        $this->assertArrayHasKey('reconnection_timestamp', $metadata);
        $this->assertArrayHasKey('disconnection_duration', $metadata);
        $this->assertArrayHasKey('previous_status', $metadata);
        $this->assertArrayHasKey('custom_field', $metadata);
        $this->assertEquals('test_value', $metadata['custom_field']);
        $this->assertEquals('temporarily_disconnected', $metadata['previous_status']);
        $this->assertEqualsWithDelta(300, $metadata['disconnection_duration'], 2); // 5分 = 300秒（±2秒の誤差許容）
    }

    #[Test]
    public function getReconnectionStatsReturnsAccurateStatistics()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 再接続ログを複数作成
        for ($i = 0; $i < 3; $i++) {
            $disconnectedAt = now()->subHours(2)->addMinutes($i * 30);
            $reconnectedAt = $disconnectedAt->copy()->addMinutes(5); // 5分後に再接続

            ConnectionLog::create([
                'user_id' => $this->user->id,
                'context_type' => 'room',
                'context_id' => 1,
                'status' => 'connected',
                'disconnected_at' => $disconnectedAt,
                'reconnected_at' => $reconnectedAt,
                'created_at' => $disconnectedAt,
                'metadata' => []
            ]);
        }

        $stats = $this->handler->getReconnectionStats($this->user->id, $context);

        $this->assertEquals(3, $stats['reconnection_count']);
        $this->assertEquals(300, $stats['average_disconnection_duration']); // 5分 = 300秒
        $this->assertEquals(24, $stats['analysis_period_hours']);
    }

    #[Test]
    public function getReconnectionStatsHandlesEmptyStatisticsProperly()
    {
        $context = ['type' => 'room', 'id' => 1];

        $stats = $this->handler->getReconnectionStats($this->user->id, $context);

        $this->assertEquals(0, $stats['reconnection_count']);
        $this->assertEquals(0, $stats['average_disconnection_duration']);
        $this->assertEquals(24, $stats['analysis_period_hours']);
    }

    #[Test]
    public function handlePreventsDuplicateReconnection()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 進行中の再接続を作成
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'connected',
            'reconnected_at' => now(),
            'metadata' => []
        ]);

        // 一時切断ログも作成（重複チェックのため）
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'temporarily_disconnected',
            'disconnected_at' => now()->subMinutes(1),
            'metadata' => []
        ]);

        $result = $this->handler->handle($this->user->id, $context);

        $this->assertFalse($result);
    }

    #[Test]
    public function handleAllowsReconnectionEvenAfterGracePeriodExpired()
    {
        $context = ['type' => 'room', 'id' => 1];

        // 猶予期間を大幅に超過した一時切断ログを作成
        ConnectionLog::create([
            'user_id' => $this->user->id,
            'context_type' => 'room',
            'context_id' => 1,
            'status' => 'temporarily_disconnected',
            'disconnected_at' => now()->subHours(2), // 2時間前
            'metadata' => []
        ]);

        $result = $this->handler->handle($this->user->id, $context);

        // 猶予期間は超過しているが、再接続は許可される
        $this->assertTrue($result);
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
