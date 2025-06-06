<?php

namespace Tests\Unit\Services;

use App\Services\DebateService;
use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use App\Events\TurnAdvanced;
use App\Events\DebateFinished;
use App\Jobs\AdvanceDebateTurnJob;
use App\Jobs\EvaluateDebateJob;
use App\Jobs\GenerateAIResponseJob;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Tests\Helpers\MockHelpers;

/**
 * DebateService テストクラス
 *
 * ディベート開始、終了、フォーマット取得の基本機能をテスト
 */
class DebateServiceTest extends BaseServiceTest
{
    private DebateService $debateService;

    protected function setUp(): void
    {
        parent::setUp();

        // AI ユーザーIDの設定
        Config::set('app.ai_user_id', 1);
        $this->debateService = new DebateService();
    }

    protected function tearDown(): void
    {
        // Mockのクリーンアップはparent::tearDown()で処理される
        parent::tearDown();
    }

    // =========================================================================
    // startDebate() テスト
    // =========================================================================

    public function test_startDebate_SetsCurrentTurnAndTurnEndTime()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_READY]);
        $debate = $this->createDebate(['room_id' => $room->id]);

        // フォーマットをMock
        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn([
                1 => ['duration' => 60, 'speaker' => 'affirmative', 'name' => 'First Turn'],
                2 => ['duration' => 60, 'speaker' => 'negative', 'name' => 'Second Turn'],
            ]);

        $startTime = Carbon::parse('2024-01-01 00:00:00');
        Carbon::setTestNow($startTime);

        // Act
        $this->debateService->startDebate($debate);

        // Assert
        $debate->refresh();
        $this->assertEquals(1, $debate->current_turn);
        // 時間の比較は秒単位で行う
        $expectedTime = $startTime->copy()->addSeconds(68);
        $this->assertEquals($expectedTime->timestamp, $debate->turn_end_time->timestamp);

        // イベントとジョブの確認
        Event::assertDispatched(TurnAdvanced::class);
        Queue::assertPushed(AdvanceDebateTurnJob::class);
    }

    public function test_startDebate_WithAIUser_DispatchesGenerateAIResponseJob()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        // AIユーザーIDをConfigから取得してユーザーを作成
        $aiUserId = config('app.ai_user_id', 1);
        $aiUser = User::factory()->create();
        $this->app['config']->set('app.ai_user_id', $aiUser->id);

        $humanUser = $this->createUser();
        $room = $this->createRoom(['status' => Room::STATUS_READY, 'is_ai_debate' => true]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $aiUser->id, // AIが先攻
            'negative_user_id' => $humanUser->id,
        ]);

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn([
                1 => ['duration' => 60, 'speaker' => 'affirmative', 'name' => 'First Turn'],
            ]);

        // DebateServiceを再作成して新しいAI UserIDを反映
        $this->debateService = new DebateService();

        // Act
        $this->debateService->startDebate($debate);

        // Assert
        Queue::assertPushed(GenerateAIResponseJob::class, function ($job) use ($debate) {
            return $job->debateId === $debate->id && $job->currentTurn === 1;
        });
        Queue::assertPushed(AdvanceDebateTurnJob::class);
    }

    public function test_startDebate_WithHumanUser_OnlyDispatchesAdvanceDebateTurnJob()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        // AIユーザーIDをConfigから取得してユーザーを作成
        $aiUser = User::factory()->create();
        $this->app['config']->set('app.ai_user_id', $aiUser->id);

        $humanUser = $this->createUser();
        $room = $this->createRoom(['status' => Room::STATUS_READY, 'is_ai_debate' => true]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $humanUser->id, // Humanが先攻
            'negative_user_id' => $aiUser->id,
        ]);

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn([
                1 => ['duration' => 60, 'speaker' => 'affirmative', 'name' => 'First Turn'],
            ]);

        // DebateServiceを再作成して新しいAI UserIDを反映
        $this->debateService = new DebateService();

        // Act
        $this->debateService->startDebate($debate);

        // Assert
        Queue::assertNotPushed(GenerateAIResponseJob::class);
        Queue::assertPushed(AdvanceDebateTurnJob::class);
    }

    public function test_startDebate_WithInvalidFormat_ThrowsException()
    {
        // Arrange
        $room = $this->createRoom(['status' => Room::STATUS_READY]);
        $debate = $this->createDebate(['room_id' => $room->id]);

        // 空のフォーマットを返すようにMock
        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn([]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Debate format is invalid.');

        $this->debateService->startDebate($debate);
    }

    // =========================================================================
    // finishDebate() テスト
    // =========================================================================

    public function test_finishDebate_UpdatesRoomStatusAndClearsTurnEndTime()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'turn_end_time' => Carbon::now()->addMinutes(5),
        ]);

        // Act
        $this->debateService->finishDebate($debate);

        // Assert
        $room->refresh();
        $debate->refresh();

        $this->assertEquals(Room::STATUS_FINISHED, $room->status);
        $this->assertNull($debate->turn_end_time);

        // イベントとジョブの確認
        Event::assertDispatched(DebateFinished::class);
        Queue::assertPushed(EvaluateDebateJob::class);
    }

    public function test_finishDebate_WithNonDebatingRoom_DoesNotUpdateStatus()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_FINISHED]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'turn_end_time' => Carbon::now()->addMinutes(5),
        ]);

        // Act
        $this->debateService->finishDebate($debate);

        // Assert
        $room->refresh();
        $debate->refresh();

        // ステータスは変更されない
        $this->assertEquals(Room::STATUS_FINISHED, $room->status);
        // turn_end_timeはクリアされる
        $this->assertNull($debate->turn_end_time);

        // イベントとジョブは実行される
        Event::assertDispatched(DebateFinished::class);
        Queue::assertPushed(EvaluateDebateJob::class);
    }

    public function test_finishDebate_WithDeletedRoom_DoesNotThrowException()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        // 削除されたRoomを持つDebateをテスト
        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'turn_end_time' => Carbon::now()->addMinutes(5),
        ]);

        // Roomを削除（ソフトデリート）
        $room->delete();

        // Act & Assert - 例外が投げられないことを確認
        $this->debateService->finishDebate($debate);

        $debate->refresh();
        $this->assertNull($debate->turn_end_time);

        Event::assertDispatched(DebateFinished::class);
        Queue::assertPushed(EvaluateDebateJob::class);
    }

    // =========================================================================
    // getFormat() テスト
    // =========================================================================

    public function test_getFormat_ReturnsDebateFormat()
    {
        // Arrange
        $room = $this->createRoom();
        $debate = $this->createDebate(['room_id' => $room->id]);

        $expectedFormat = [
            1 => ['duration' => 300, 'speaker' => 'affirmative', 'name' => 'Constructive 1'],
            2 => ['duration' => 300, 'speaker' => 'negative', 'name' => 'Constructive 2'],
        ];

        Cache::shouldReceive('remember')
            ->withAnyArgs()
            ->atLeast()->once()
            ->andReturn($expectedFormat);

        // Act
        $result = $this->debateService->getFormat($debate);

        // Assert
        $this->assertEquals($expectedFormat, $result);
    }

    public function test_getFormat_CachesResult()
    {
        // Arrange
        $room = $this->createRoom();
        $debate = $this->createDebate(['room_id' => $room->id]);

        $expectedFormat = [
            1 => ['duration' => 300, 'speaker' => 'affirmative', 'name' => 'Test Turn'],
        ];

        // 単純にキャッシュが呼ばれることを確認
        Cache::shouldReceive('remember')
            ->andReturn($expectedFormat);

        // Act & Assert
        $result = $this->debateService->getFormat($debate);

        $this->assertEquals($expectedFormat, $result);
    }

    public function test_getFormat_WithDifferentLocale_UsesDifferentCacheKey()
    {
        // Arrange
        $room = $this->createRoom();
        $debate = $this->createDebate(['room_id' => $room->id]);

        $enFormat = ['en' => 'format'];
        $jaFormat = ['ja' => 'format'];

        Cache::shouldReceive('remember')
            ->with("debate_format_{$debate->room_id}_en", 60, \Mockery::type('Closure'))
            ->atLeast()->once()
            ->andReturn($enFormat);

        Cache::shouldReceive('remember')
            ->with("debate_format_{$debate->room_id}_ja", 60, \Mockery::type('Closure'))
            ->atLeast()->once()
            ->andReturn($jaFormat);

        // Act & Assert
        app()->setLocale('en');
        $resultEn = $this->debateService->getFormat($debate);
        $this->assertEquals($enFormat, $resultEn);

        app()->setLocale('ja');
        $resultJa = $this->debateService->getFormat($debate);
        $this->assertEquals($jaFormat, $resultJa);
    }

    // =========================================================================
    // 統合テスト
    // =========================================================================

    public function test_startDebate_Integration_CompleteFlow()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        // AIユーザーIDをConfigから取得してユーザーを作成
        $aiUser = User::factory()->create();
        $this->app['config']->set('app.ai_user_id', $aiUser->id);

        $humanUser = $this->createUser();
        $room = $this->createRoom([
            'status' => Room::STATUS_READY,
            'is_ai_debate' => true,
        ]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $humanUser->id,
            'negative_user_id' => $aiUser->id,
        ]);

        $format = [
            1 => ['duration' => 120, 'speaker' => 'affirmative', 'name' => 'Opening Statement'],
            2 => ['duration' => 120, 'speaker' => 'negative', 'name' => 'Counter Statement'],
        ];

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn($format);

        $startTime = Carbon::parse('2024-01-01 00:00:00');
        Carbon::setTestNow($startTime);

        // DebateServiceを再作成して新しいAI UserIDを反映
        $this->debateService = new DebateService();

        // Act
        $this->debateService->startDebate($debate);

        // Assert
        $debate->refresh();
        $this->assertEquals(1, $debate->current_turn);
        $expectedTime = $startTime->copy()->addSeconds(128);
        $this->assertEquals($expectedTime->timestamp, $debate->turn_end_time->timestamp);

        // Humanターンなので、AIジョブは発火しない
        Queue::assertNotPushed(GenerateAIResponseJob::class);
        Queue::assertPushed(AdvanceDebateTurnJob::class, function ($job) use ($debate) {
            return $job->debateId === $debate->id && $job->expectedTurn === 1;
        });

        Event::assertDispatched(TurnAdvanced::class, function ($event) use ($debate) {
            return $event->debate->id === $debate->id &&
                isset($event->additionalData['current_turn']) &&
                $event->additionalData['current_turn'] === 1 &&
                isset($event->additionalData['speaker']) &&
                $event->additionalData['speaker'] === 'affirmative';
        });
    }

    // =========================================================================
    // TODO-022: ターン管理テスト
    // =========================================================================

    public function test_advanceToNextTurn_UpdatesCurrentTurnAndTime()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'current_turn' => 1,
        ]);

        $format = [
            1 => ['duration' => 60, 'speaker' => 'affirmative', 'name' => 'First Turn'],
            2 => ['duration' => 90, 'speaker' => 'negative', 'name' => 'Second Turn'],
        ];

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn($format);

        $startTime = Carbon::parse('2024-01-01 00:00:00');
        Carbon::setTestNow($startTime);

        // Act
        $this->debateService->advanceToNextTurn($debate, 1);

        // Assert
        $debate->refresh();
        $this->assertEquals(2, $debate->current_turn);
        $expectedTime = $startTime->copy()->addSeconds(92); // 90 + 2 buffer
        $this->assertEquals($expectedTime->timestamp, $debate->turn_end_time->timestamp);

        Event::assertDispatched(TurnAdvanced::class);
        Queue::assertPushed(AdvanceDebateTurnJob::class);
    }

    public function test_advanceToNextTurn_WithExpectedTurnMismatch_SkipsAdvancement()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'current_turn' => 2,
        ]);

        // Act
        $this->debateService->advanceToNextTurn($debate, 1); // expectedTurn が current_turn と一致しない

        // Assert
        $debate->refresh();
        $this->assertEquals(2, $debate->current_turn); // 変更されない

        Event::assertNotDispatched(TurnAdvanced::class);
        Queue::assertNotPushed(AdvanceDebateTurnJob::class);
    }

    public function test_advanceToNextTurn_WithNonDebatingRoom_SkipsAdvancement()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_FINISHED]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'current_turn' => 1,
        ]);

        // Act
        $this->debateService->advanceToNextTurn($debate, 1);

        // Assert
        $debate->refresh();
        $this->assertEquals(1, $debate->current_turn); // 変更されない

        Event::assertNotDispatched(TurnAdvanced::class);
        Queue::assertNotPushed(AdvanceDebateTurnJob::class);
    }

    public function test_advanceToNextTurn_WithNoNextTurn_FinishesDebate()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'current_turn' => 2,
        ]);

        $format = [
            1 => ['duration' => 60, 'speaker' => 'affirmative', 'name' => 'First Turn'],
            2 => ['duration' => 60, 'speaker' => 'negative', 'name' => 'Second Turn'],
            // 3番目のターンは存在しない
        ];

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn($format);

        // Act
        $this->debateService->advanceToNextTurn($debate, 2);

        // Assert
        $room->refresh();
        $debate->refresh();

        $this->assertEquals(Room::STATUS_FINISHED, $room->status);
        $this->assertNull($debate->turn_end_time);

        Event::assertDispatched(DebateFinished::class);
        Queue::assertPushed(EvaluateDebateJob::class);
    }

    public function test_advanceToNextTurn_WithAITurn_DispatchesGenerateAIResponseJob()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $aiUser = User::factory()->create();
        $this->app['config']->set('app.ai_user_id', $aiUser->id);

        $humanUser = $this->createUser();
        $room = $this->createRoom(['status' => Room::STATUS_DEBATING, 'is_ai_debate' => true]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'current_turn' => 1,
            'affirmative_user_id' => $humanUser->id,
            'negative_user_id' => $aiUser->id, // 2番目のターンはAI
        ]);

        $format = [
            1 => ['duration' => 60, 'speaker' => 'affirmative', 'name' => 'First Turn'],
            2 => ['duration' => 60, 'speaker' => 'negative', 'name' => 'Second Turn'], // AI turn
        ];

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn($format);

        // DebateServiceを再作成して新しいAI UserIDを反映
        $this->debateService = new DebateService();

        // Act
        $this->debateService->advanceToNextTurn($debate, 1);

        // Assert
        Queue::assertPushed(GenerateAIResponseJob::class, function ($job) use ($debate) {
            return $job->debateId === $debate->id && $job->currentTurn === 2;
        });
        Queue::assertPushed(AdvanceDebateTurnJob::class);
    }

    public function test_getNextTurn_ReturnsCorrectNextTurn()
    {
        // Arrange
        $room = $this->createRoom();
        $debate = $this->createDebate(['room_id' => $room->id, 'current_turn' => 1]);

        $format = [
            1 => ['duration' => 60, 'speaker' => 'affirmative'],
            2 => ['duration' => 60, 'speaker' => 'negative'],
        ];

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn($format);

        // Act
        $nextTurn = $this->debateService->getNextTurn($debate);

        // Assert
        $this->assertEquals(2, $nextTurn);
    }

    public function test_getNextTurn_ReturnsNullWhenNoNextTurn()
    {
        // Arrange
        $room = $this->createRoom();
        $debate = $this->createDebate(['room_id' => $room->id, 'current_turn' => 2]);

        $format = [
            1 => ['duration' => 60, 'speaker' => 'affirmative'],
            2 => ['duration' => 60, 'speaker' => 'negative'],
        ];

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn($format);

        // Act
        $nextTurn = $this->debateService->getNextTurn($debate);

        // Assert
        $this->assertNull($nextTurn);
    }

    public function test_updateTurn_UpdatesCurrentTurnAndEndTime()
    {
        // Arrange
        $room = $this->createRoom();
        $debate = $this->createDebate(['room_id' => $room->id, 'current_turn' => 1]);

        $format = [
            2 => ['duration' => 120, 'speaker' => 'negative'],
        ];

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn($format);

        $startTime = Carbon::parse('2024-01-01 00:00:00');
        Carbon::setTestNow($startTime);

        // Act
        $this->debateService->updateTurn($debate, 2);

        // Assert
        $debate->refresh();
        $this->assertEquals(2, $debate->current_turn);
        $expectedTime = $startTime->copy()->addSeconds(122); // 120 + 2 buffer
        $this->assertEquals($expectedTime->timestamp, $debate->turn_end_time->timestamp);
    }

    public function test_updateTurn_WithInvalidTurn_ThrowsException()
    {
        // Arrange
        $room = $this->createRoom();
        $debate = $this->createDebate(['room_id' => $room->id, 'current_turn' => 1]);

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn([]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Debate format is invalid or turn number is out of bounds.');

        $this->debateService->updateTurn($debate, 99);
    }

    public function test_isQuestioningTurn_ReturnsCorrectValue()
    {
        // Arrange
        $room = $this->createRoom();
        $debate = $this->createDebate(['room_id' => $room->id]);

        $format = [
            1 => ['duration' => 60, 'speaker' => 'affirmative'],
            2 => ['duration' => 60, 'speaker' => 'negative', 'is_questions' => true],
        ];

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn($format);

        // Act & Assert
        $this->assertFalse($this->debateService->isQuestioningTurn($debate, 1));
        $this->assertTrue($this->debateService->isQuestioningTurn($debate, 2));
        $this->assertFalse($this->debateService->isQuestioningTurn($debate, 99)); // 存在しないターン
    }

    // =========================================================================
    // TODO-023: 早期終了テスト
    // =========================================================================

    public function test_requestEarlyTermination_SuccessfulRequest()
    {
        // Arrange
        Event::fake();
        Queue::fake();
        Cache::flush();

        $humanUser = $this->createUser();
        $room = $this->createRoom([
            'status' => Room::STATUS_DEBATING,
            'format_type' => 'free',
        ]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $humanUser->id,
            'negative_user_id' => $this->createUser()->id,
        ]);

        // Room::isFreeFormat() をPartialMockで設定
        $roomMock = \Mockery::mock($room)->makePartial();
        $roomMock->shouldReceive('isFreeFormat')->andReturn(true);
        $debate->setRelation('room', $roomMock);

        // Act
        $result = $this->debateService->requestEarlyTermination($debate, $humanUser->id);

        // Assert
        $this->assertTrue($result);

        // キャッシュに状態が保存されているか確認
        $cacheKey = $this->debateService->getCacheKey($debate->id);
        $requestData = Cache::get($cacheKey);
        $this->assertNotNull($requestData);
        $this->assertEquals($humanUser->id, $requestData['requested_by']);
        $this->assertEquals('requested', $requestData['status']);

        Event::assertDispatched(\App\Events\EarlyTerminationRequested::class);
        Queue::assertPushed(\App\Jobs\EarlyTerminationTimeoutJob::class);
    }

    public function test_requestEarlyTermination_WithNonFreeFormat_ReturnsFalse()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $humanUser = $this->createUser();
        $room = $this->createRoom([
            'status' => Room::STATUS_DEBATING,
            'format_type' => 'academic',
        ]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $humanUser->id,
            'negative_user_id' => $this->createUser()->id,
        ]);

        // Room::isFreeFormat() をPartialMockで設定
        $roomMock = \Mockery::mock($room)->makePartial();
        $roomMock->shouldReceive('isFreeFormat')->andReturn(false);
        $debate->setRelation('room', $roomMock);

        // Act
        $result = $this->debateService->requestEarlyTermination($debate, $humanUser->id);

        // Assert
        $this->assertFalse($result);

        Event::assertNotDispatched(\App\Events\EarlyTerminationRequested::class);
        Queue::assertNotPushed(\App\Jobs\EarlyTerminationTimeoutJob::class);
    }

    public function test_requestEarlyTermination_WithNonDebatingRoom_ReturnsFalse()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $humanUser = $this->createUser();
        $room = $this->createRoom([
            'status' => Room::STATUS_FINISHED,
            'format_type' => 'free',
        ]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $humanUser->id,
            'negative_user_id' => $this->createUser()->id,
        ]);

        // Room::isFreeFormat() をPartialMockで設定
        $roomMock = \Mockery::mock($room)->makePartial();
        $roomMock->shouldReceive('isFreeFormat')->andReturn(true);
        $debate->setRelation('room', $roomMock);

        // Act
        $result = $this->debateService->requestEarlyTermination($debate, $humanUser->id);

        // Assert
        $this->assertFalse($result);

        Event::assertNotDispatched(\App\Events\EarlyTerminationRequested::class);
        Queue::assertNotPushed(\App\Jobs\EarlyTerminationTimeoutJob::class);
    }

    public function test_requestEarlyTermination_WithNonParticipant_ReturnsFalse()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $nonParticipant = $this->createUser();
        $room = $this->createRoom([
            'status' => Room::STATUS_DEBATING,
            'format_type' => 'free',
        ]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->createUser()->id,
            'negative_user_id' => $this->createUser()->id,
        ]);

        // Room::isFreeFormat() をPartialMockで設定
        $roomMock = \Mockery::mock($room)->makePartial();
        $roomMock->shouldReceive('isFreeFormat')->andReturn(true);
        $debate->setRelation('room', $roomMock);

        // Act
        $result = $this->debateService->requestEarlyTermination($debate, $nonParticipant->id);

        // Assert
        $this->assertFalse($result);

        Event::assertNotDispatched(\App\Events\EarlyTerminationRequested::class);
        Queue::assertNotPushed(\App\Jobs\EarlyTerminationTimeoutJob::class);
    }

    public function test_requestEarlyTermination_WithExistingRequest_ReturnsFalse()
    {
        // Arrange
        Event::fake();
        Queue::fake();
        Cache::flush();

        $humanUser = $this->createUser();
        $room = $this->createRoom([
            'status' => Room::STATUS_DEBATING,
            'format_type' => 'free',
        ]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $humanUser->id,
            'negative_user_id' => $this->createUser()->id,
        ]);

        // Room::isFreeFormat() をPartialMockで設定
        $roomMock = \Mockery::mock($room)->makePartial();
        $roomMock->shouldReceive('isFreeFormat')->andReturn(true);
        $debate->setRelation('room', $roomMock);

        // 既存のリクエストをキャッシュに設定
        $cacheKey = $this->debateService->getCacheKey($debate->id);
        Cache::put($cacheKey, ['status' => 'requested'], 60);

        // Act
        $result = $this->debateService->requestEarlyTermination($debate, $humanUser->id);

        // Assert
        $this->assertFalse($result);

        Event::assertNotDispatched(\App\Events\EarlyTerminationRequested::class);
        Queue::assertNotPushed(\App\Jobs\EarlyTerminationTimeoutJob::class);
    }

    public function test_respondToEarlyTermination_WithAgree_FinishesDebate()
    {
        // Arrange
        Event::fake();
        Queue::fake();
        Cache::flush();

        $affirmativeUser = $this->createUser();
        $negativeUser = $this->createUser();
        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        // 既存のリクエストをキャッシュに設定
        $cacheKey = $this->debateService->getCacheKey($debate->id);
        Cache::put($cacheKey, ['requested_by' => $affirmativeUser->id, 'status' => 'requested'], 90);

        // Act
        $result = $this->debateService->respondToEarlyTermination($debate, $negativeUser->id, true);

        // Assert
        $this->assertTrue($result);

        // キャッシュから削除されているか確認
        $this->assertNull(Cache::get($cacheKey));

        // ディベートが終了しているか確認
        $room->refresh();
        $debate->refresh();
        $this->assertEquals(Room::STATUS_FINISHED, $room->status);
        $this->assertNull($debate->turn_end_time);

        Event::assertDispatched(\App\Events\EarlyTerminationAgreed::class);
        Event::assertDispatched(DebateFinished::class);
        Queue::assertPushed(EvaluateDebateJob::class);
    }

    public function test_respondToEarlyTermination_WithDecline_ContinuesDebate()
    {
        // Arrange
        Event::fake();
        Queue::fake();
        Cache::flush();

        $affirmativeUser = $this->createUser();
        $negativeUser = $this->createUser();
        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        // 既存のリクエストをキャッシュに設定
        $cacheKey = $this->debateService->getCacheKey($debate->id);
        Cache::put($cacheKey, ['requested_by' => $affirmativeUser->id, 'status' => 'requested'], 90);

        // Act
        $result = $this->debateService->respondToEarlyTermination($debate, $negativeUser->id, false);

        // Assert
        $this->assertTrue($result);

        // キャッシュから削除されているか確認
        $this->assertNull(Cache::get($cacheKey));

        // ディベートが継続しているか確認
        $room->refresh();
        $this->assertEquals(Room::STATUS_DEBATING, $room->status);

        Event::assertDispatched(\App\Events\EarlyTerminationDeclined::class);
        Event::assertNotDispatched(DebateFinished::class);
        Queue::assertNotPushed(EvaluateDebateJob::class);
    }

    public function test_respondToEarlyTermination_WithNoActiveRequest_ReturnsFalse()
    {
        // Arrange
        Event::fake();
        Queue::fake();
        Cache::flush();

        $affirmativeUser = $this->createUser();
        $negativeUser = $this->createUser();
        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        // Act
        $result = $this->debateService->respondToEarlyTermination($debate, $negativeUser->id, true);

        // Assert
        $this->assertFalse($result);

        Event::assertNotDispatched(\App\Events\EarlyTerminationAgreed::class);
        Event::assertNotDispatched(\App\Events\EarlyTerminationDeclined::class);
    }

    public function test_respondToEarlyTermination_WithSameUser_ReturnsFalse()
    {
        // Arrange
        Event::fake();
        Queue::fake();
        Cache::flush();

        $affirmativeUser = $this->createUser();
        $negativeUser = $this->createUser();
        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        // 既存のリクエストをキャッシュに設定
        $cacheKey = $this->debateService->getCacheKey($debate->id);
        Cache::put($cacheKey, ['requested_by' => $affirmativeUser->id, 'status' => 'requested'], 90);

        // Act - 提案者が自分で応答しようとする
        $result = $this->debateService->respondToEarlyTermination($debate, $affirmativeUser->id, true);

        // Assert
        $this->assertFalse($result);

        Event::assertNotDispatched(\App\Events\EarlyTerminationAgreed::class);
        Event::assertNotDispatched(\App\Events\EarlyTerminationDeclined::class);
    }

    public function test_getEarlyTerminationStatus_ReturnsCorrectStatus()
    {
        // Arrange
        Cache::flush();

        $affirmativeUser = $this->createUser();
        $room = $this->createRoom();
        $debate = $this->createDebate(['room_id' => $room->id]);

        // Act & Assert - リクエストなしの場合
        $status = $this->debateService->getEarlyTerminationStatus($debate);
        $this->assertEquals(['status' => 'none'], $status);

        // リクエストありの場合
        $cacheKey = $this->debateService->getCacheKey($debate->id);
        $requestData = [
            'requested_by' => $affirmativeUser->id,
            'status' => 'requested',
            'timestamp' => now()->toISOString(),
        ];
        Cache::put($cacheKey, $requestData, 90);

        $status = $this->debateService->getEarlyTerminationStatus($debate);
        $this->assertEquals([
            'status' => 'requested',
            'requested_by' => $affirmativeUser->id,
            'timestamp' => $requestData['timestamp'],
        ], $status);
    }

    public function test_isFreeFormat_ReturnsCorrectValue()
    {
        // Arrange
        $room = $this->createRoom();
        $debate = $this->createDebate(['room_id' => $room->id]);

        // Room::isFreeFormat() をPartialMockで設定
        $roomMock = \Mockery::mock($room)->makePartial();
        $roomMock->shouldReceive('isFreeFormat')->andReturn(true);
        $debate->setRelation('room', $roomMock);

        // Act & Assert
        $this->assertTrue($this->debateService->isFreeFormat($debate));
    }

    public function test_getCacheKey_ReturnsCorrectKey()
    {
        // Act
        $cacheKey = $this->debateService->getCacheKey(123);

        // Assert
        $this->assertEquals('early_termination_request_123', $cacheKey);
    }

    // =========================================================================
    // TODO-024: イベント・ジョブテスト
    // =========================================================================

    public function test_startDebate_DispatchesTurnAdvancedEvent()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_READY]);
        $debate = $this->createDebate(['room_id' => $room->id]);

        $format = [
            1 => ['duration' => 60, 'speaker' => 'affirmative', 'name' => 'First Turn'],
        ];

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn($format);

        // Act
        $this->debateService->startDebate($debate);

        // Assert
        Event::assertDispatched(TurnAdvanced::class, function ($event) use ($debate) {
            return $event->debate->id === $debate->id &&
                is_array($event->additionalData) &&
                isset($event->additionalData['current_turn']) &&
                $event->additionalData['current_turn'] === 1 &&
                isset($event->additionalData['speaker']) &&
                $event->additionalData['speaker'] === 'affirmative';
        });
    }

    public function test_startDebate_DispatchesAdvanceDebateTurnJob()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_READY]);
        $debate = $this->createDebate(['room_id' => $room->id]);

        $format = [
            1 => ['duration' => 60, 'speaker' => 'affirmative', 'name' => 'First Turn'],
        ];

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn($format);

        // Act
        $this->debateService->startDebate($debate);

        // Assert
        Queue::assertPushed(AdvanceDebateTurnJob::class, function ($job) use ($debate) {
            return $job->debateId === $debate->id && $job->expectedTurn === 1;
        });
    }

    public function test_finishDebate_DispatchesDebateFinishedEvent()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate(['room_id' => $room->id]);

        // Act
        $this->debateService->finishDebate($debate);

        // Assert
        Event::assertDispatched(DebateFinished::class, function ($event) use ($debate) {
            return $event->debateId === $debate->id;
        });
    }

    public function test_finishDebate_DispatchesEvaluateDebateJob()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate(['room_id' => $room->id]);

        // Act
        $this->debateService->finishDebate($debate);

        // Assert
        Queue::assertPushed(EvaluateDebateJob::class);
    }

    public function test_requestEarlyTermination_DispatchesEarlyTerminationRequestedEvent()
    {
        // Arrange
        Event::fake();
        Queue::fake();
        Cache::flush();

        $humanUser = $this->createUser();
        $room = $this->createRoom([
            'status' => Room::STATUS_DEBATING,
            'format_type' => 'free',
        ]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $humanUser->id,
            'negative_user_id' => $this->createUser()->id,
        ]);

        // Room::isFreeFormat() をPartialMockで設定
        $roomMock = \Mockery::mock($room)->makePartial();
        $roomMock->shouldReceive('isFreeFormat')->andReturn(true);
        $debate->setRelation('room', $roomMock);

        // Act
        $this->debateService->requestEarlyTermination($debate, $humanUser->id);

        // Assert
        Event::assertDispatched(\App\Events\EarlyTerminationRequested::class, function ($event) use ($debate, $humanUser) {
            return $event->debateId === $debate->id && $event->requestedBy === $humanUser->id;
        });
    }

    public function test_requestEarlyTermination_DispatchesEarlyTerminationTimeoutJob()
    {
        // Arrange
        Event::fake();
        Queue::fake();
        Cache::flush();

        $humanUser = $this->createUser();
        $room = $this->createRoom([
            'status' => Room::STATUS_DEBATING,
            'format_type' => 'free',
        ]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $humanUser->id,
            'negative_user_id' => $this->createUser()->id,
        ]);

        // Room::isFreeFormat() をPartialMockで設定
        $roomMock = \Mockery::mock($room)->makePartial();
        $roomMock->shouldReceive('isFreeFormat')->andReturn(true);
        $debate->setRelation('room', $roomMock);

        // Act
        $this->debateService->requestEarlyTermination($debate, $humanUser->id);

        // Assert
        Queue::assertPushed(\App\Jobs\EarlyTerminationTimeoutJob::class, function ($job) use ($debate, $humanUser) {
            return $job->debateId === $debate->id && $job->requestedBy === $humanUser->id;
        });
    }

    public function test_respondToEarlyTermination_WithAgree_DispatchesEarlyTerminationAgreedEvent()
    {
        // Arrange
        Event::fake();
        Queue::fake();
        Cache::flush();

        $affirmativeUser = $this->createUser();
        $negativeUser = $this->createUser();
        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        // 既存のリクエストをキャッシュに設定
        $cacheKey = $this->debateService->getCacheKey($debate->id);
        Cache::put($cacheKey, ['requested_by' => $affirmativeUser->id, 'status' => 'requested'], 90);

        // Act
        $this->debateService->respondToEarlyTermination($debate, $negativeUser->id, true);

        // Assert
        Event::assertDispatched(\App\Events\EarlyTerminationAgreed::class, function ($event) use ($debate) {
            return $event->debateId === $debate->id;
        });
    }

    public function test_respondToEarlyTermination_WithDecline_DispatchesEarlyTerminationDeclinedEvent()
    {
        // Arrange
        Event::fake();
        Queue::fake();
        Cache::flush();

        $affirmativeUser = $this->createUser();
        $negativeUser = $this->createUser();
        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        // 既存のリクエストをキャッシュに設定
        $cacheKey = $this->debateService->getCacheKey($debate->id);
        Cache::put($cacheKey, ['requested_by' => $affirmativeUser->id, 'status' => 'requested'], 90);

        // Act
        $this->debateService->respondToEarlyTermination($debate, $negativeUser->id, false);

        // Assert
        Event::assertDispatched(\App\Events\EarlyTerminationDeclined::class, function ($event) use ($debate) {
            return $event->debateId === $debate->id;
        });
    }

    public function test_createEventData_ReturnsCorrectEventData()
    {
        // Arrange
        $room = $this->createRoom();
        $debate = $this->createDebate(['room_id' => $room->id]);

        $format = [
            1 => [
                'duration' => 60,
                'speaker' => 'affirmative',
                'name' => 'Opening Statement',
                'is_prep_time' => false,
            ],
            2 => [
                'duration' => 30,
                'speaker' => 'negative',
                'name' => 'Prep Time',
                'is_prep_time' => true,
            ],
        ];

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn($format);

        $debate->turn_end_time = Carbon::parse('2024-01-01 12:00:00');

        // Act
        $reflection = new \ReflectionClass($this->debateService);
        $method = $reflection->getMethod('createEventData');
        $method->setAccessible(true);

        $eventData1 = $method->invoke($this->debateService, $debate, 1);
        $eventData2 = $method->invoke($this->debateService, $debate, 2);

        // Assert
        $this->assertEquals([
            'turn_number' => 1,
            'current_turn' => 1,
            'turn_end_time' => $debate->turn_end_time->timestamp,
            'speaker' => 'affirmative',
            'is_prep_time' => false,
        ], $eventData1);

        $this->assertEquals([
            'turn_number' => 2,
            'current_turn' => 2,
            'turn_end_time' => $debate->turn_end_time->timestamp,
            'speaker' => 'negative',
            'is_prep_time' => true,
        ], $eventData2);
    }

    public function test_createEventData_WithInvalidTurn_ReturnsDefaultData()
    {
        // Arrange
        $room = $this->createRoom();
        $debate = $this->createDebate(['room_id' => $room->id]);

        Cache::shouldReceive('remember')
            ->atLeast()->once()
            ->andReturn([]);

        $debate->turn_end_time = Carbon::parse('2024-01-01 12:00:00');

        // Act
        $reflection = new \ReflectionClass($this->debateService);
        $method = $reflection->getMethod('createEventData');
        $method->setAccessible(true);

        $eventData = $method->invoke($this->debateService, $debate, 99);

        // Assert
        $this->assertEquals([
            'turn_number' => 99,
            'current_turn' => 99,
            'turn_end_time' => $debate->turn_end_time->timestamp,
            'speaker' => null,
            'is_prep_time' => false,
        ], $eventData);
    }
}
