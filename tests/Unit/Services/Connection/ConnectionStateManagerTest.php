<?php

namespace Tests\Unit\Services\Connection;

use Tests\Unit\Services\BaseServiceTest;
use App\Services\Connection\ConnectionStateManager;
use App\Enums\ConnectionStatus;
use App\Models\ConnectionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConnectionStateManagerTest extends BaseServiceTest
{
    use RefreshDatabase;

    private ConnectionStateManager $stateManager;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateManager = new ConnectionStateManager();
        $this->testUser = User::factory()->create();
    }

    public function test_validates_transition_from_connected_to_temporarily_disconnected()
    {
        $result = $this->stateManager->validateTransition(
            ConnectionStatus::CONNECTED,
            ConnectionStatus::TEMPORARILY_DISCONNECTED
        );

        $this->assertTrue($result);
    }

    public function test_validates_transition_from_connected_to_gracefully_disconnected()
    {
        $result = $this->stateManager->validateTransition(
            ConnectionStatus::CONNECTED,
            ConnectionStatus::GRACEFULLY_DISCONNECTED
        );

        $this->assertTrue($result);
    }

    public function test_validates_transition_from_temporarily_disconnected_to_connected()
    {
        $result = $this->stateManager->validateTransition(
            ConnectionStatus::TEMPORARILY_DISCONNECTED,
            ConnectionStatus::CONNECTED
        );

        $this->assertTrue($result);
    }

    public function test_validates_transition_from_temporarily_disconnected_to_disconnected()
    {
        $result = $this->stateManager->validateTransition(
            ConnectionStatus::TEMPORARILY_DISCONNECTED,
            ConnectionStatus::DISCONNECTED
        );

        $this->assertTrue($result);
    }

    public function test_rejects_invalid_transition_from_disconnected()
    {
        $result = $this->stateManager->validateTransition(
            ConnectionStatus::DISCONNECTED,
            ConnectionStatus::CONNECTED
        );

        $this->assertFalse($result);
    }

    public function test_rejects_invalid_transition_from_gracefully_disconnected()
    {
        $result = $this->stateManager->validateTransition(
            ConnectionStatus::GRACEFULLY_DISCONNECTED,
            ConnectionStatus::CONNECTED
        );

        $this->assertFalse($result);
    }

    public function test_validates_log_transition_with_null_log_for_initial_connection()
    {
        $result = $this->stateManager->validateLogTransition(
            null,
            ConnectionStatus::CONNECTED
        );

        $this->assertTrue($result);
    }

    public function test_rejects_log_transition_with_null_log_for_non_connected_status()
    {
        $result = $this->stateManager->validateLogTransition(
            null,
            ConnectionStatus::TEMPORARILY_DISCONNECTED
        );

        $this->assertFalse($result);
    }

    public function test_validates_log_transition_with_existing_log()
    {
        $log = ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'status' => ConnectionStatus::CONNECTED
        ]);

        $result = $this->stateManager->validateLogTransition(
            $log,
            ConnectionStatus::TEMPORARILY_DISCONNECTED
        );

        $this->assertTrue($result);
    }

    public function test_can_reconnect_with_null_log()
    {
        $result = $this->stateManager->canReconnect(null);
        $this->assertTrue($result);
    }

    public function test_can_reconnect_with_temporarily_disconnected_log()
    {
        $log = ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'status' => ConnectionStatus::TEMPORARILY_DISCONNECTED
        ]);

        $result = $this->stateManager->canReconnect($log);
        $this->assertTrue($result);
    }

    public function test_cannot_reconnect_with_connected_log()
    {
        $log = ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'status' => ConnectionStatus::CONNECTED
        ]);

        $result = $this->stateManager->canReconnect($log);
        $this->assertFalse($result);
    }

    public function test_can_disconnect_with_connected_log()
    {
        $log = ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'status' => ConnectionStatus::CONNECTED
        ]);

        $result = $this->stateManager->canDisconnect($log);
        $this->assertTrue($result);
    }

    public function test_cannot_disconnect_with_null_log()
    {
        $result = $this->stateManager->canDisconnect(null);
        $this->assertFalse($result);
    }

    public function test_cannot_disconnect_with_temporarily_disconnected_log()
    {
        $log = ConnectionLog::factory()->create([
            'user_id' => $this->testUser->id,
            'status' => ConnectionStatus::TEMPORARILY_DISCONNECTED
        ]);

        $result = $this->stateManager->canDisconnect($log);
        $this->assertFalse($result);
    }

    public function test_gets_valid_statuses()
    {
        $statuses = $this->stateManager->getValidStatuses();

        $this->assertIsArray($statuses);
        $this->assertContains(ConnectionStatus::CONNECTED, $statuses);
        $this->assertContains(ConnectionStatus::TEMPORARILY_DISCONNECTED, $statuses);
        $this->assertContains(ConnectionStatus::DISCONNECTED, $statuses);
        $this->assertContains(ConnectionStatus::GRACEFULLY_DISCONNECTED, $statuses);
        $this->assertCount(4, $statuses);
    }

    public function test_gets_status_description()
    {
        $this->assertEquals('接続中', $this->stateManager->getStatusDescription(ConnectionStatus::CONNECTED));
        $this->assertEquals('一時的切断', $this->stateManager->getStatusDescription(ConnectionStatus::TEMPORARILY_DISCONNECTED));
        $this->assertEquals('切断済み', $this->stateManager->getStatusDescription(ConnectionStatus::DISCONNECTED));
        $this->assertEquals('正常切断', $this->stateManager->getStatusDescription(ConnectionStatus::GRACEFULLY_DISCONNECTED));
        $this->assertEquals('不明な状態', $this->stateManager->getStatusDescription('invalid_status'));
    }

    public function test_identifies_final_status()
    {
        $this->assertTrue($this->stateManager->isFinalStatus(ConnectionStatus::DISCONNECTED));
        $this->assertTrue($this->stateManager->isFinalStatus(ConnectionStatus::GRACEFULLY_DISCONNECTED));
        $this->assertFalse($this->stateManager->isFinalStatus(ConnectionStatus::CONNECTED));
        $this->assertFalse($this->stateManager->isFinalStatus(ConnectionStatus::TEMPORARILY_DISCONNECTED));
    }
}
