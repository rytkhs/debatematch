<?php

namespace Tests\Unit\Services\Connection;

use Tests\TestCase;
use Mockery;
use App\Services\Connection\ConnectionCoordinator;
use App\Services\Connection\ConnectionStateManager;
use App\Services\Connection\ConnectionLogger;
use App\Services\Connection\DisconnectionHandler;
use App\Services\Connection\ReconnectionHandler;
use App\Services\Connection\ConnectionAnalyzer;
use App\Models\ConnectionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConnectionCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    private ConnectionCoordinator $coordinator;
    private $stateManager;
    private $logger;
    private $disconnectionHandler;
    private $reconnectionHandler;
    private $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        // モックの作成
        $this->stateManager = Mockery::mock(ConnectionStateManager::class);
        $this->logger = Mockery::mock(ConnectionLogger::class);
        $this->disconnectionHandler = Mockery::mock(DisconnectionHandler::class);
        $this->reconnectionHandler = Mockery::mock(ReconnectionHandler::class);
        $this->analyzer = Mockery::mock(ConnectionAnalyzer::class);

        // ConnectionCoordinatorのインスタンス作成
        $this->coordinator = new ConnectionCoordinator(
            $this->stateManager,
            $this->logger,
            $this->disconnectionHandler,
            $this->reconnectionHandler,
            $this->analyzer
        );
    }

    public function test_recordInitialConnection_delegates_to_logger()
    {
        $userId = 1;
        $context = ['type' => 'room', 'id' => 123];
        $mockLog = Mockery::mock(ConnectionLog::class);

        $this->logger->shouldReceive('recordInitialConnection')
            ->once()
            ->with($userId, $context)
            ->andReturn($mockLog);

        $result = $this->coordinator->recordInitialConnection($userId, $context);

        $this->assertSame($mockLog, $result);
    }

    public function test_handleDisconnection_delegates_to_disconnection_handler()
    {
        $userId = 1;
        $context = ['type' => 'room', 'id' => 123];
        $expectedResult = 'disconnection_result';

        $this->disconnectionHandler->shouldReceive('handle')
            ->once()
            ->with($userId, $context)
            ->andReturn($expectedResult);

        $result = $this->coordinator->handleDisconnection($userId, $context);

        $this->assertEquals($expectedResult, $result);
    }

    public function test_handleReconnection_delegates_to_reconnection_handler()
    {
        $userId = 1;
        $context = ['type' => 'room', 'id' => 123];

        $this->reconnectionHandler->shouldReceive('handle')
            ->once()
            ->with($userId, $context)
            ->andReturn(true);

        $result = $this->coordinator->handleReconnection($userId, $context);

        $this->assertTrue($result);
    }

    public function test_finalizeDisconnection_delegates_to_disconnection_handler()
    {
        $userId = 1;
        $context = ['type' => 'room', 'id' => 123];

        $this->disconnectionHandler->shouldReceive('finalizeDisconnection')
            ->once()
            ->with($userId, $context)
            ->andReturnNull();

        $this->coordinator->finalizeDisconnection($userId, $context);

        // メソッドが呼ばれたことを確認（戻り値なし）
        $this->assertTrue(true);
    }

    public function test_getConnectionState_returns_log_status()
    {
        $userId = 1;
        $context = ['type' => 'room', 'id' => 123];

        // 実際のデータベースのConnectionLogを作成
        $user = User::factory()->create(['id' => $userId]);
        $mockLog = ConnectionLog::create([
            'user_id' => $userId,
            'context_type' => 'room',
            'context_id' => 123,
            'status' => 'connected',
            'connected_at' => now(),
            'metadata' => []
        ]);

        $result = $this->coordinator->getConnectionState($userId, $context);

        $this->assertEquals('connected', $result);
    }

    public function test_getConnectionState_returns_null_when_no_log()
    {
        $userId = 999; // 存在しないユーザーID
        $context = ['type' => 'room', 'id' => 123];

        $result = $this->coordinator->getConnectionState($userId, $context);

        $this->assertNull($result);
    }

    public function test_getConnectionQuality_delegates_to_analyzer()
    {
        $userId = 1;
        $hours = 24;
        $expectedStats = ['quality_score' => 95, 'total_connections' => 10];

        $this->analyzer->shouldReceive('getBasicConnectionStats')
            ->once()
            ->with($userId, $hours)
            ->andReturn($expectedStats);

        $result = $this->coordinator->getConnectionQuality($userId, $hours);

        $this->assertEquals($expectedStats, $result);
    }

    public function test_detectAnomalousPatterns_delegates_to_analyzer()
    {
        $userId = 1;
        $context = ['type' => 'room', 'id' => 123];
        $expectedPatterns = ['frequent_disconnections' => true];

        $this->analyzer->shouldReceive('detectAnomalousPatterns')
            ->once()
            ->with($userId, $context)
            ->andReturn($expectedPatterns);

        $result = $this->coordinator->detectAnomalousPatterns($userId, $context);

        $this->assertEquals($expectedPatterns, $result);
    }

    public function test_updateLastSeen_handles_connected_state()
    {
        $userId = 1;
        $context = ['type' => 'room', 'id' => 123];

        // 実際の接続状態のログを作成
        $user = User::factory()->create(['id' => $userId]);
        ConnectionLog::create([
            'user_id' => $userId,
            'context_type' => 'room',
            'context_id' => 123,
            'status' => 'connected',
            'connected_at' => now(),
            'metadata' => []
        ]);

        $this->logger->shouldReceive('updateHeartbeat')
            ->once()
            ->with($userId, $context);

        $this->coordinator->updateLastSeen($userId, $context);

        $this->assertTrue(true); // アサーションの確認
    }

    public function test_updateLastSeen_handles_temporarily_disconnected_state()
    {
        $userId = 2;
        $context = ['type' => 'room', 'id' => 123];

        // 実際の一時切断状態のログを作成
        $user = User::factory()->create(['id' => $userId]);
        ConnectionLog::create([
            'user_id' => $userId,
            'context_type' => 'room',
            'context_id' => 123,
            'status' => 'temporarily_disconnected',
            'connected_at' => now()->subMinutes(5),
            'disconnected_at' => now()->subMinutes(1),
            'metadata' => []
        ]);

        $this->reconnectionHandler->shouldReceive('handle')
            ->once()
            ->with($userId, $context)
            ->andReturn(true);

        $this->coordinator->updateLastSeen($userId, $context);

        $this->assertTrue(true); // アサーションの確認
    }

    public function test_updateLastSeen_handles_no_log_state()
    {
        $userId = 3;
        $context = ['type' => 'room', 'id' => 123];

        // ユーザーは存在するが、ログは存在しない状態
        $user = User::factory()->create(['id' => $userId]);

        $this->logger->shouldReceive('recordInitialConnection')
            ->once()
            ->with($userId, $context)
            ->andReturn(Mockery::mock(ConnectionLog::class));

        $this->coordinator->updateLastSeen($userId, $context);

        $this->assertTrue(true); // アサーションの確認
    }

    public function test_error_handling_logs_and_rethrows_exceptions()
    {
        $userId = 1;
        $context = ['type' => 'room', 'id' => 123];

        $this->logger->shouldReceive('recordInitialConnection')
            ->once()
            ->with($userId, $context)
            ->andThrow(new \Exception('Test exception'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->coordinator->recordInitialConnection($userId, $context);
    }

    public function test_finalize_disconnection_does_not_throw_exceptions()
    {
        $userId = 1;
        $context = ['type' => 'room', 'id' => 123];

        $this->disconnectionHandler->shouldReceive('finalizeDisconnection')
            ->once()
            ->with($userId, $context)
            ->andThrow(new \Exception('Test exception'));

        // finalizeDisconnectionは例外を投げないことを確認
        $this->coordinator->finalizeDisconnection($userId, $context);

        $this->assertTrue(true); // 例外が投げられなかったことを確認
    }

    public function test_slack_notification_sent_for_critical_operations()
    {
        // SlackNotifierのモックを作成
        $slackNotifier = Mockery::mock(\App\Services\SlackNotifier::class);
        $this->app->instance(\App\Services\SlackNotifier::class, $slackNotifier);

        $userId = 1;
        $context = ['type' => 'debate', 'id' => 123];

        // 重要操作（disconnection）でエラーが発生
        $this->disconnectionHandler->shouldReceive('handle')
            ->once()
            ->with($userId, $context)
            ->andThrow(new \Exception('Critical debate disconnection error'));

        // Slack通知が送信されることを確認
        $slackNotifier->shouldReceive('send')
            ->once()
            ->with(
                Mockery::pattern('/🚨.*接続システム重要エラー.*disconnection.*Critical debate disconnection error/s'),
                null,
                'Connection Alert Bot',
                ':warning:'
            )
            ->andReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Critical debate disconnection error');

        $this->coordinator->handleDisconnection($userId, $context);
    }

    public function test_slack_notification_handles_failure_gracefully()
    {
        // SlackNotifierのモックを作成（送信失敗をシミュレート）
        $slackNotifier = Mockery::mock(\App\Services\SlackNotifier::class);
        $this->app->instance(\App\Services\SlackNotifier::class, $slackNotifier);

        $userId = 1;
        $context = ['type' => 'debate', 'id' => 123];

        // 重要操作でエラーが発生
        $this->disconnectionHandler->shouldReceive('handle')
            ->once()
            ->with($userId, $context)
            ->andThrow(new \Exception('Critical error'));

        // Slack通知が失敗
        $slackNotifier->shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('Slack API error'));

        // Slack通知の失敗がシステムを止めないことを確認
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Critical error');

        $this->coordinator->handleDisconnection($userId, $context);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
