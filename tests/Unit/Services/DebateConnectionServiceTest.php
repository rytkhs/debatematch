<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\Test;
use App\Services\DebateConnectionService;
use App\Services\ConnectionManager;
use App\Services\DebateService;
use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;

class DebateConnectionServiceTest extends BaseServiceTest
{
    use RefreshDatabase;

    /** @var DebateConnectionService */
    protected $service;

    /** @var MockInterface|ConnectionManager */
    protected $connectionManagerMock;

    /** @var MockInterface|DebateService */
    protected $debateServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionManagerMock = $this->createServiceMock(ConnectionManager::class);
        $this->debateServiceMock = $this->createServiceMock(DebateService::class);
        $this->service = new DebateConnectionService($this->connectionManagerMock, $this->debateServiceMock);
    }

    #[Test]
    public function testConstructor()
    {
        $connectionManager = Mockery::mock(ConnectionManager::class);
        $debateService = Mockery::mock(DebateService::class);
        $service = new DebateConnectionService($connectionManager, $debateService);

        $this->assertInstanceOf(DebateConnectionService::class, $service);
    }

    #[Test]
    public function testInitialize()
    {
        $debateId = 1;

        // 現在の実装では何もしないため、例外が発生しないことを確認
        $this->service->initialize($debateId);

        // 成功すれば例外は発生しない
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function testHandleUserDisconnection_Success()
    {
        $userId = 1;
        $debateId = 2;
        $expectedResult = true;

        $this->connectionManagerMock
            ->shouldReceive('handleDisconnection')
            ->once()
            ->with($userId, [
                'type' => 'debate',
                'id' => $debateId
            ])
            ->andReturn($expectedResult);

        $result = $this->service->handleUserDisconnection($userId, $debateId);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function testHandleUserDisconnection_WithException()
    {
        $userId = 1;
        $debateId = 2;
        $exception = new \Exception('Connection error');

        Log::shouldReceive('error')
            ->once()
            ->with('ディベート切断処理中にエラー発生', [
                'userId' => $userId,
                'debateId' => $debateId,
                'error' => $exception->getMessage()
            ]);

        $this->connectionManagerMock
            ->shouldReceive('handleDisconnection')
            ->once()
            ->andThrow($exception);

        $result = $this->service->handleUserDisconnection($userId, $debateId);

        $this->assertNull($result);
    }

    #[Test]
    public function testHandleUserReconnection_Success()
    {
        $userId = 1;
        $debateId = 2;
        $expectedResult = true;

        $this->connectionManagerMock
            ->shouldReceive('handleReconnection')
            ->once()
            ->with($userId, [
                'type' => 'debate',
                'id' => $debateId
            ])
            ->andReturn($expectedResult);

        $result = $this->service->handleUserReconnection($userId, $debateId);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function testHandleUserReconnection_WithException()
    {
        $userId = 1;
        $debateId = 2;
        $exception = new \Exception('Reconnection error');

        Log::shouldReceive('error')
            ->once()
            ->with('ディベート再接続処理中にエラー発生', [
                'userId' => $userId,
                'debateId' => $debateId,
                'error' => $exception->getMessage()
            ]);

        $this->connectionManagerMock
            ->shouldReceive('handleReconnection')
            ->once()
            ->andThrow($exception);

        $result = $this->service->handleUserReconnection($userId, $debateId);

        $this->assertNull($result);
    }

    #[Test]
    public function testTerminateDebate_Success()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $room = $this->createRoom(['created_by' => $user1->id]);
        $debate = $this->createDebate(['room_id' => $room->id, 'affirmative_user_id' => $user1->id, 'negative_user_id' => $user2->id]);

        $this->debateServiceMock
            ->shouldReceive('terminateDebate')
            ->once()
            ->with(Mockery::on(function ($arg) use ($debate) {
                return $arg instanceof Debate && $arg->id === $debate->id;
            }))
            ->andReturn(true);

        Log::shouldReceive('info')
            ->once()
            ->with('ディベートが強制終了されました', [
                'debateId' => $debate->id,
                'reason' => 'connection_lost'
            ]);

        $result = $this->service->terminateDebate($debate->id);

        $this->assertInstanceOf(Debate::class, $result);
        $this->assertEquals($debate->id, $result->id);
    }

    #[Test]
    public function testTerminateDebate_WithCustomReason()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $room = $this->createRoom(['created_by' => $user1->id]);
        $debate = $this->createDebate(['room_id' => $room->id, 'affirmative_user_id' => $user1->id, 'negative_user_id' => $user2->id]);

        $customReason = 'admin_intervention';

        $this->debateServiceMock
            ->shouldReceive('terminateDebate')
            ->once()
            ->with(Mockery::on(function ($arg) use ($debate) {
                return $arg instanceof Debate && $arg->id === $debate->id;
            }))
            ->andReturn(true);

        Log::shouldReceive('info')
            ->once()
            ->with('ディベートが強制終了されました', [
                'debateId' => $debate->id,
                'reason' => $customReason
            ]);

        $result = $this->service->terminateDebate($debate->id, $customReason);

        $this->assertInstanceOf(Debate::class, $result);
        $this->assertEquals($debate->id, $result->id);
    }

    #[Test]
    public function testTerminateDebate_DebateNotFound()
    {
        $debateId = 999;

        Log::shouldReceive('warning')
            ->once()
            ->with('終了対象のディベートが見つかりません', ['debateId' => $debateId]);

        $result = $this->service->terminateDebate($debateId);

        $this->assertNull($result);
    }

    #[Test]
    public function testTerminateDebate_WithException()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $room = $this->createRoom(['created_by' => $user1->id]);
        $debate = $this->createDebate(['room_id' => $room->id, 'affirmative_user_id' => $user1->id, 'negative_user_id' => $user2->id]);

        $exception = new \Exception('Termination failed');
        $reason = 'test_error';

        $this->debateServiceMock
            ->shouldReceive('terminateDebate')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('error')
            ->once()
            ->with('ディベート強制終了処理中にエラー発生', [
                'debateId' => $debate->id,
                'reason' => $reason,
                'error' => $exception->getMessage()
            ]);

        $result = $this->service->terminateDebate($debate->id, $reason);

        $this->assertNull($result);
    }

    #[Test]
    public function testTerminateDebate_EagerLoading()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $room = $this->createRoom(['created_by' => $user1->id]);
        $debate = $this->createDebate(['room_id' => $room->id, 'affirmative_user_id' => $user1->id, 'negative_user_id' => $user2->id]);

        $this->debateServiceMock
            ->shouldReceive('terminateDebate')
            ->once()
            ->with(Mockery::on(function ($arg) use ($debate, $room) {
                // Eager loadingでroomが読み込まれていることを確認
                return $arg instanceof Debate &&
                    $arg->id === $debate->id &&
                    $arg->relationLoaded('room') &&
                    $arg->room->id === $room->id;
            }))
            ->andReturn(true);

        Log::shouldReceive('info')->once();

        $result = $this->service->terminateDebate($debate->id);

        $this->assertInstanceOf(Debate::class, $result);
    }

    #[Test]
    public function testTerminateDebate_MultipleReasons()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $room = $this->createRoom(['created_by' => $user1->id]);
        $debate = $this->createDebate(['room_id' => $room->id, 'affirmative_user_id' => $user1->id, 'negative_user_id' => $user2->id]);

        $reasons = ['timeout', 'user_disconnect', 'system_error', 'admin_action'];

        $this->debateServiceMock
            ->shouldReceive('terminateDebate')
            ->times(count($reasons))
            ->andReturn(true);

        Log::shouldReceive('info')
            ->times(count($reasons));

        foreach ($reasons as $reason) {
            $result = $this->service->terminateDebate($debate->id, $reason);
            $this->assertInstanceOf(Debate::class, $result);
        }
    }

    #[Test]
    public function testConnectionManagerIntegration()
    {
        // ConnectionManagerとの統合確認
        $userId = 1;
        $debateId = 2;

        // 切断と再接続の連続処理
        $this->connectionManagerMock
            ->shouldReceive('handleDisconnection')
            ->once()
            ->with($userId, ['type' => 'debate', 'id' => $debateId])
            ->andReturn(true);

        $this->connectionManagerMock
            ->shouldReceive('handleReconnection')
            ->once()
            ->with($userId, ['type' => 'debate', 'id' => $debateId])
            ->andReturn(true);

        $disconnectResult = $this->service->handleUserDisconnection($userId, $debateId);
        $reconnectResult = $this->service->handleUserReconnection($userId, $debateId);

        $this->assertTrue($disconnectResult);
        $this->assertTrue($reconnectResult);
    }

    #[Test]
    public function testDebateServiceIntegration()
    {
        // DebateServiceとの統合確認
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $room = $this->createRoom(['created_by' => $user1->id]);
        $debate = $this->createDebate(['room_id' => $room->id, 'affirmative_user_id' => $user1->id, 'negative_user_id' => $user2->id]);

        $this->debateServiceMock
            ->shouldReceive('terminateDebate')
            ->once()
            ->andReturn(true);

        Log::shouldReceive('info')->once();

        $result = $this->service->terminateDebate($debate->id);

        $this->assertInstanceOf(Debate::class, $result);
    }
}
