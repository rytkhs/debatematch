<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\Test;
use App\Services\RoomConnectionService;
use App\Services\ConnectionManager;
use App\Models\Room;
use App\Models\User;
use App\Events\UserLeftRoom;
use App\Events\CreatorLeftRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;

class RoomConnectionServiceTest extends BaseServiceTest
{
    use RefreshDatabase;

    /** @var RoomConnectionService */
    protected $service;

    /** @var MockInterface|ConnectionManager */
    protected $connectionManagerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionManagerMock = $this->createServiceMock(ConnectionManager::class);
        $this->service = new RoomConnectionService($this->connectionManagerMock);
    }

    #[Test]
    public function testConstructor()
    {
        $connectionManager = Mockery::mock(ConnectionManager::class);
        $service = new RoomConnectionService($connectionManager);

        $this->assertInstanceOf(RoomConnectionService::class, $service);
    }

    #[Test]
    public function testInitialize()
    {
        $roomId = 1;

        // 現在の実装では何もしないため、例外が発生しないことを確認
        $this->service->initialize($roomId);

        // 成功すれば例外は発生しない
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function testHandleUserDisconnection_Success()
    {
        $userId = 1;
        $roomId = 2;
        $expectedResult = true;

        $this->connectionManagerMock
            ->shouldReceive('handleDisconnection')
            ->once()
            ->with($userId, [
                'type' => 'room',
                'id' => $roomId
            ])
            ->andReturn($expectedResult);

        $result = $this->service->handleUserDisconnection($userId, $roomId);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function testHandleUserDisconnection_WithException()
    {
        $userId = 1;
        $roomId = 2;
        $exception = new \Exception('Connection error');

        Log::shouldReceive('error')
            ->once()
            ->with('ルーム切断処理中にエラー発生', [
                'userId' => $userId,
                'roomId' => $roomId,
                'error' => $exception->getMessage()
            ]);

        $this->connectionManagerMock
            ->shouldReceive('handleDisconnection')
            ->once()
            ->andThrow($exception);

        $result = $this->service->handleUserDisconnection($userId, $roomId);

        $this->assertNull($result);
    }

    #[Test]
    public function testHandleUserReconnection_Success()
    {
        $userId = 1;
        $roomId = 2;
        $expectedResult = true;

        $this->connectionManagerMock
            ->shouldReceive('handleReconnection')
            ->once()
            ->with($userId, [
                'type' => 'room',
                'id' => $roomId
            ])
            ->andReturn($expectedResult);

        $result = $this->service->handleUserReconnection($userId, $roomId);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function testHandleUserReconnection_WithException()
    {
        $userId = 1;
        $roomId = 2;
        $exception = new \Exception('Reconnection error');

        Log::shouldReceive('error')
            ->once()
            ->with('ルーム再接続処理中にエラー発生', [
                'userId' => $userId,
                'roomId' => $roomId,
                'error' => $exception->getMessage()
            ]);

        $this->connectionManagerMock
            ->shouldReceive('handleReconnection')
            ->once()
            ->andThrow($exception);

        $result = $this->service->handleUserReconnection($userId, $roomId);

        $this->assertNull($result);
    }

    #[Test]
    public function testHandleUserDisconnectionTimeout_ParticipantUser()
    {
        $user = $this->createUser();
        $creator = $this->createUser();
        $room = $this->createRoom(['created_by' => $creator->id, 'status' => Room::STATUS_READY]);

        // 参加者として追加
        $room->users()->attach($user->id, ['side' => 'affirmative']);

        Event::fake();
        Log::shouldReceive('info')
            ->once()
            ->with('ユーザーがタイムアウトによりルームから退出しました', [
                'userId' => $user->id,
                'roomId' => $room->id
            ]);

        $this->service->handleUserDisconnectionTimeout($user->id, $room->id);

        // ユーザーがルームから削除されたことを確認
        $room->refresh();
        $this->assertFalse($room->users->contains($user->id));

        // ルームステータスがWAITINGに変更されたことを確認
        $this->assertEquals(Room::STATUS_WAITING, $room->status);

        // UserLeftRoomイベントが発火されたことを確認
        Event::assertDispatched(UserLeftRoom::class, function ($event) use ($room, $user) {
            return $event->room->id === $room->id && $event->user->id === $user->id;
        });

        // CreatorLeftRoomイベントは発火されないことを確認
        Event::assertNotDispatched(CreatorLeftRoom::class);
    }

    #[Test]
    public function testHandleUserDisconnectionTimeout_CreatorUser()
    {
        $creator = $this->createUser();
        $participant = $this->createUser();
        $room = $this->createRoom(['created_by' => $creator->id, 'status' => Room::STATUS_READY]);

        // 作成者と参加者を追加
        $room->users()->attach($creator->id, ['side' => 'affirmative']);
        $room->users()->attach($participant->id, ['side' => 'negative']);

        Event::fake();
        Log::shouldReceive('info')
            ->once()
            ->with('ユーザーがタイムアウトによりルームから退出しました', [
                'userId' => $creator->id,
                'roomId' => $room->id
            ]);

        $this->service->handleUserDisconnectionTimeout($creator->id, $room->id);

        // 作成者がルームから削除されたことを確認
        $room->refresh();
        $this->assertFalse($room->users->contains($creator->id));

        // ルームが強制終了状態になったことを確認
        $room->refresh();
        $this->assertEquals(Room::STATUS_TERMINATED, $room->status);

        // CreatorLeftRoomイベントが発火されたことを確認（他の参加者がいるため）
        Event::assertDispatched(CreatorLeftRoom::class, function ($event) use ($room, $creator) {
            return $event->room->id === $room->id && $event->creator->id === $creator->id;
        });
    }

    #[Test]
    public function testHandleUserDisconnectionTimeout_CreatorAloneInRoom()
    {
        $creator = $this->createUser();
        $room = $this->createRoom(['created_by' => $creator->id, 'status' => Room::STATUS_READY]);

        // 作成者のみ
        $room->users()->attach($creator->id, ['side' => 'affirmative']);

        Event::fake();
        Log::shouldReceive('info')
            ->once()
            ->with('ユーザーがタイムアウトによりルームから退出しました', [
                'userId' => $creator->id,
                'roomId' => $room->id
            ]);

        $this->service->handleUserDisconnectionTimeout($creator->id, $room->id);

        // ルームが強制終了状態になったことを確認
        $room->refresh();
        $this->assertEquals(Room::STATUS_TERMINATED, $room->status);

        // 他の参加者がいないため、CreatorLeftRoomイベントは発火されない
        Event::assertNotDispatched(CreatorLeftRoom::class);
    }

    #[Test]
    public function testHandleUserDisconnectionTimeout_RoomNotFound()
    {
        $userId = 1;
        $roomId = 999;

        Log::shouldReceive('warning')
            ->once()
            ->with('タイムアウト処理のためのルームまたはユーザーが見つかりません', [
                'userId' => $userId,
                'roomId' => $roomId
            ]);

        $this->service->handleUserDisconnectionTimeout($userId, $roomId);

        // ログ出力以外は何も起こらない
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function testHandleUserDisconnectionTimeout_UserNotFound()
    {
        $userId = 999;
        $room = $this->createRoom();

        Log::shouldReceive('warning')
            ->once()
            ->with('タイムアウト処理のためのルームまたはユーザーが見つかりません', [
                'userId' => $userId,
                'roomId' => $room->id
            ]);

        $this->service->handleUserDisconnectionTimeout($userId, $room->id);

        // ログ出力以外は何も起こらない
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function testHandleUserDisconnectionTimeout_WithSoftDeletedUser()
    {
        $user = $this->createUser();
        $room = $this->createRoom(['created_by' => $user->id, 'status' => Room::STATUS_READY]);

        // ユーザーをソフトデリート
        $user->delete();

        // ルームに参加者として追加
        $room->users()->attach($user->id, ['side' => 'affirmative']);

        Event::fake();
        Log::shouldReceive('info')
            ->once()
            ->with('ユーザーがタイムアウトによりルームから退出しました', [
                'userId' => $user->id,
                'roomId' => $room->id
            ]);

        $this->service->handleUserDisconnectionTimeout($user->id, $room->id);

        // ユーザーがルームから削除されたことを確認
        $room->refresh();
        $this->assertFalse($room->users->contains($user->id));

        // ルームが強制終了状態になったことを確認（作成者だったため）
        $room->refresh();
        $this->assertEquals(Room::STATUS_TERMINATED, $room->status);
    }

    #[Test]
    public function testHandleUserDisconnectionTimeout_WithException()
    {
        $user = $this->createUser();
        $room = $this->createRoom(['created_by' => $user->id]);

        // データベースエラーを意図的に発生させる（例：不正なRoom状態）
        Log::shouldReceive('error')
            ->once()
            ->with('ルーム切断タイムアウト処理中にエラー発生', Mockery::type('array'));

        // トランザクション内でエラーを発生させる
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database transaction failed'));

        $this->service->handleUserDisconnectionTimeout($user->id, $room->id);

        // エラーログが出力されること以外は確認しない（ロールバックされるため）
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function testHandleUserDisconnectionTimeout_StatusTransitions()
    {
        $user = $this->createUser();

        // WAITING状態のルーム
        $waitingRoom = $this->createRoom(['created_by' => $user->id, 'status' => Room::STATUS_WAITING]);
        $waitingRoom->users()->attach($user->id, ['side' => 'affirmative']);

        Log::shouldReceive('info')->twice();

        $this->service->handleUserDisconnectionTimeout($user->id, $waitingRoom->id);

        $waitingRoom->refresh();
        $this->assertEquals(Room::STATUS_TERMINATED, $waitingRoom->status); // 作成者退出のため強制終了

        // DEBATING状態のルーム
        $debatingRoom = $this->createRoom(['created_by' => $user->id, 'status' => Room::STATUS_DEBATING]);
        $debatingRoom->users()->attach($user->id, ['side' => 'affirmative']);

        $this->service->handleUserDisconnectionTimeout($user->id, $debatingRoom->id);

        $debatingRoom->refresh();
        $this->assertEquals(Room::STATUS_TERMINATED, $debatingRoom->status); // 作成者退出のため強制終了
    }
}
