<?php

namespace Tests\Unit\Services\Connection;

use Tests\Unit\Services\BaseServiceTest;
use App\Services\Connection\ConnectionLogger;
use App\Services\Connection\ConnectionStateManager;
use App\Enums\ConnectionStatus;
use App\Models\ConnectionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class ConnectionLoggerTest extends BaseServiceTest
{
    use RefreshDatabase;

    private ConnectionLogger $logger;
    private ConnectionStateManager $stateManager;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateManager = new ConnectionStateManager();
        $this->logger = new ConnectionLogger($this->stateManager);
        $this->testUser = User::factory()->create();
    }

    public function test_records_initial_connection_successfully()
    {
        $context = [
            'type' => 'room',
            'id' => 1
        ];

        $log = $this->logger->recordInitialConnection($this->testUser->id, $context);

        $this->assertInstanceOf(ConnectionLog::class, $log);
        $this->assertEquals($this->testUser->id, $log->user_id);
        $this->assertEquals('room', $log->context_type);
        $this->assertEquals(1, $log->context_id);
        $this->assertEquals(ConnectionStatus::CONNECTED, $log->status);
        $this->assertNotNull($log->connected_at);
    }

    public function test_returns_existing_log_when_already_connected()
    {
        $context = [
            'type' => 'room',
            'id' => 1
        ];

        // 最初の接続
        $firstLog = $this->logger->recordInitialConnection($this->testUser->id, $context);

        // 二回目の接続試行
        $secondLog = $this->logger->recordInitialConnection($this->testUser->id, $context);

        $this->assertEquals($firstLog->id, $secondLog->id);
    }

    public function test_returns_null_for_invalid_user()
    {
        $context = [
            'type' => 'room',
            'id' => 1
        ];

        $log = $this->logger->recordInitialConnection(99999, $context);

        $this->assertNull($log);
    }

    public function test_records_disconnection_successfully()
    {
        $context = [
            'type' => 'room',
            'id' => 1
        ];

        // まず接続ログを作成
        ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'context_type' => $context['type'],
            'context_id' => $context['id'],
            'status' => ConnectionStatus::CONNECTED
        ]);

        $disconnectionLog = $this->logger->recordDisconnection($this->testUser->id, $context);

        $this->assertInstanceOf(ConnectionLog::class, $disconnectionLog);
        $this->assertEquals(ConnectionStatus::TEMPORARILY_DISCONNECTED, $disconnectionLog->status);
        $this->assertNotNull($disconnectionLog->disconnected_at);
        $this->assertIsArray($disconnectionLog->metadata);
        $this->assertEquals('disconnection', $disconnectionLog->metadata['connection_type']);
    }

    public function test_skips_disconnection_when_already_disconnected()
    {
        $context = [
            'type' => 'room',
            'id' => 1
        ];

        // 既に切断されたログを作成
        ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'context_type' => $context['type'],
            'context_id' => $context['id'],
            'status' => ConnectionStatus::TEMPORARILY_DISCONNECTED
        ]);

        $result = $this->logger->recordDisconnection($this->testUser->id, $context);

        $this->assertNull($result);
    }

    public function test_records_reconnection_successfully()
    {
        $context = [
            'type' => 'room',
            'id' => 1
        ];

        // 切断されたログを作成
        $disconnectedLog = ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'context_type' => $context['type'],
            'context_id' => $context['id'],
            'status' => ConnectionStatus::TEMPORARILY_DISCONNECTED,
            'disconnected_at' => now()->subMinutes(5)
        ]);

        $result = $this->logger->recordReconnection($this->testUser->id, $context);

        $this->assertTrue($result);

        // ログが更新されていることを確認
        $updatedLog = $disconnectedLog->fresh();
        $this->assertEquals(ConnectionStatus::CONNECTED, $updatedLog->status);
        $this->assertNotNull($updatedLog->reconnected_at);
        $this->assertArrayHasKey('reconnection_metadata', $updatedLog->metadata);
    }

    public function test_creates_new_log_for_reconnection_without_existing_log()
    {
        $context = [
            'type' => 'room',
            'id' => 1
        ];

        $result = $this->logger->recordReconnection($this->testUser->id, $context);

        $this->assertTrue($result);

        // 新しいログが作成されていることを確認
        $log = ConnectionLog::where('user_id', $this->testUser->id)
            ->where('context_type', $context['type'])
            ->where('context_id', $context['id'])
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(ConnectionStatus::CONNECTED, $log->status);
        $this->assertEquals('reconnection', $log->metadata['connection_type']);
    }

    public function test_skips_reconnection_when_already_connected()
    {
        $context = [
            'type' => 'room',
            'id' => 1
        ];

        // 既に接続されたログを作成
        ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'context_type' => $context['type'],
            'context_id' => $context['id'],
            'status' => ConnectionStatus::CONNECTED
        ]);

        $result = $this->logger->recordReconnection($this->testUser->id, $context);

        $this->assertFalse($result);
    }

    public function test_records_final_disconnection_successfully()
    {
        $context = [
            'type' => 'room',
            'id' => 1
        ];

        // 一時切断ログを作成
        $tempDisconnectedLog = ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'context_type' => $context['type'],
            'context_id' => $context['id'],
            'status' => ConnectionStatus::TEMPORARILY_DISCONNECTED
        ]);

        $this->logger->recordFinalDisconnection($this->testUser->id, $context);

        // ログが更新されていることを確認
        $updatedLog = $tempDisconnectedLog->fresh();
        $this->assertEquals(ConnectionStatus::DISCONNECTED, $updatedLog->status);
        $this->assertArrayHasKey('finalized_at', $updatedLog->metadata);
    }

    public function test_updates_heartbeat_successfully()
    {
        $context = [
            'type' => 'room',
            'id' => 1
        ];

        // 接続ログを作成
        $connectedLog = ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'context_type' => $context['type'],
            'context_id' => $context['id'],
            'status' => ConnectionStatus::CONNECTED,
            'metadata' => []
        ]);

        $this->logger->updateHeartbeat($this->testUser->id, $context);

        // ハートビートが更新されていることを確認
        $updatedLog = $connectedLog->fresh();
        $this->assertArrayHasKey('last_heartbeat', $updatedLog->metadata);
    }

    public function test_skips_heartbeat_update_for_disconnected_user()
    {
        $context = [
            'type' => 'room',
            'id' => 1
        ];

        // 切断ログを作成
        $disconnectedLog = ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'context_type' => $context['type'],
            'context_id' => $context['id'],
            'status' => ConnectionStatus::TEMPORARILY_DISCONNECTED,
            'metadata' => []
        ]);

        $this->logger->updateHeartbeat($this->testUser->id, $context);

        // ハートビートが更新されていないことを確認
        $updatedLog = $disconnectedLog->fresh();
        $this->assertArrayNotHasKey('last_heartbeat', $updatedLog->metadata);
    }

    public function test_handles_database_transaction_rollback()
    {
        $context = [
            'type' => 'room',
            'id' => 1
        ];

        // まず接続状態のログを作成しておく
        ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'context_type' => $context['type'],
            'context_id' => $context['id'],
            'status' => ConnectionStatus::CONNECTED
        ]);

        // データベースエラーをシミュレート
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->logger->recordDisconnection($this->testUser->id, $context);
    }

    public function test_builds_connection_metadata_correctly()
    {
        $context = [
            'type' => 'room',
            'id' => 1
        ];

        // まず接続状態のログを作成しておく
        ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'context_type' => $context['type'],
            'context_id' => $context['id'],
            'status' => ConnectionStatus::CONNECTED
        ]);

        $customMetadata = ['custom_field' => 'custom_value'];

        $log = $this->logger->recordDisconnection($this->testUser->id, $context, $customMetadata);

        $this->assertNotNull($log);
        $this->assertArrayHasKey('client_info', $log->metadata);
        $this->assertArrayHasKey('ip_address', $log->metadata);
        $this->assertArrayHasKey('connection_type', $log->metadata);
        $this->assertArrayHasKey('timestamp', $log->metadata);
        $this->assertArrayHasKey('custom_field', $log->metadata);
        $this->assertEquals('custom_value', $log->metadata['custom_field']);
        $this->assertEquals('disconnection', $log->metadata['connection_type']);
    }
}
