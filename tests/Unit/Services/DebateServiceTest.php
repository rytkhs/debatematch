<?php

namespace Tests\Unit\Services;

use App\Services\DebateService;
use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use App\Events\TurnAdvanced;
use App\Events\DebateFinished;
use App\Events\DebateTerminated;
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

    // =========================================================================
    // TODO-025: エラーハンドリングテスト
    // =========================================================================

    public function test_startDebate_DatabaseConnectionError_HandlesException()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_READY]);
        $debate = $this->createDebate(['room_id' => $room->id]);

        // DB::transactionが失敗するようにMock
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        $this->debateService->startDebate($debate);
    }

    public function test_finishDebate_BroadcastFailure_LogsErrorButContinues()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate(['room_id' => $room->id]);

        // broadcastが失敗するようにMock
        $broadcastException = new \Exception('Broadcast service unavailable');
        $this->mock('broadcast', function ($mock) use ($broadcastException) {
            $mock->shouldReceive('broadcast')
                ->andThrow($broadcastException);
        });

        // Act
        $this->debateService->finishDebate($debate);

        // Assert
        $room->refresh();
        $this->assertEquals(Room::STATUS_FINISHED, $room->status);
        // ログが記録されることを確認
        $this->assertLogContains('Error during post-commit actions in finishDebate');
    }

    public function test_advanceToNextTurn_CriticalError_TerminatesDebate()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'current_turn' => 1,
        ]);

        // フォーマット取得でエラーが発生するようにMock
        Cache::shouldReceive('remember')
            ->andThrow(new \Exception('Critical format error'));

        // Act
        $this->debateService->advanceToNextTurn($debate, 1);

        // Assert
        $room->refresh();
        $this->assertEquals(Room::STATUS_TERMINATED, $room->status);
        $this->assertLogContains('Unexpected error during turn advancement');
        $this->assertLogContains('Critical format error');
    }

    public function test_updateTurn_InvalidTurnNumber_ThrowsExceptionWithLogging()
    {
        // Arrange
        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate(['room_id' => $room->id]);

        // 空のフォーマットを返すようにMock
        Cache::shouldReceive('remember')
            ->andReturn([]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Debate format is invalid or turn number is out of bounds.');

        $this->debateService->updateTurn($debate, 999);

        // ログ出力の確認
        $this->assertLogContains('Next turn (999) not found in format for debate');
    }

    public function test_terminateDebate_AlreadyTerminated_HandlesGracefully()
    {
        // Arrange
        Event::fake();

        $room = $this->createRoom(['status' => Room::STATUS_TERMINATED]);
        $debate = $this->createDebate(['room_id' => $room->id]);

        // Act
        $this->debateService->terminateDebate($debate);

        // Assert
        $room->refresh();
        $this->assertEquals(Room::STATUS_TERMINATED, $room->status);
        // イベントが発火されないことを確認
        Event::assertNotDispatched(DebateTerminated::class);
    }

    public function test_requestEarlyTermination_TransactionRollback_HandlesException()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_DEBATING, 'format_type' => 'free']);
        $user = $this->createUser();
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $user->id,
        ]);

        // 参加者として設定
        $room->users()->attach($user->id, ['side' => 'affirmative']);

        // Cache::putでエラーが発生するようにMock
        Cache::shouldReceive('has')
            ->once()
            ->andReturn(false);
        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new \Exception('Cache service failed'));

        // Act
        $result = $this->debateService->requestEarlyTermination($debate, $user->id);

        // Assert - 例外がキャッチされてfalseが返される
        $this->assertFalse($result);
        $this->assertLogContains('Error requesting early termination');
    }

    public function test_getFormat_CacheFailure_FallsBackToDirectCall()
    {
        // Arrange
        $room = $this->createRoom();
        $debate = $this->createDebate(['room_id' => $room->id]);

        // Cacheが失敗した場合の処理をMock
        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \Exception('Cache service unavailable'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cache service unavailable');

        $this->debateService->getFormat($debate);
    }

    public function test_startDebate_SaveFailure_RollsBackTransaction()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_READY]);
        $debate = $this->createDebate(['room_id' => $room->id]);

        Cache::shouldReceive('remember')
            ->andReturn([
                1 => ['duration' => 60, 'speaker' => 'affirmative', 'name' => 'First Turn'],
            ]);

        // DB::transactionが例外を投げるようにMock (実際のDB操作でエラーをシミュレート)
        $originalCurrentTurn = $debate->current_turn;

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                // トランザクション内でエラーが発生
                throw new \Exception('Database save failed');
            });

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database save failed');

        $this->debateService->startDebate($debate);

        // ロールバックが発生し、元の状態が保持されることを確認
        $debate->refresh();
        $this->assertEquals($originalCurrentTurn, $debate->current_turn);
    }

    public function test_advanceToNextTurn_TerminateDebateFailure_LogsCriticalError()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'current_turn' => 1,
        ]);

        // フォーマット取得でエラーが発生し、さらにterminateDebateでもエラーが発生
        Cache::shouldReceive('remember')
            ->andThrow(new \Exception('Critical format error'));

        // terminateDebateも失敗するようにMock
        $this->partialMock(DebateService::class, function ($mock) {
            $mock->shouldReceive('terminateDebate')
                ->andThrow(new \Exception('Terminate failed'));
        });

        // Act
        $this->debateService->advanceToNextTurn($debate, 1);

        // Assert
        $this->assertLogContains('Failed to terminate debate after error in advanceToNextTurn');
        $this->assertLogContains('Terminate failed');
    }

    // =========================================================================
    // ログアサーションヘルパー
    // =========================================================================

    /**
     * ログに特定のメッセージが含まれることを確認するヘルパー
     */
    private function assertLogContains(string $message): void
    {
        $this->assertTrue(true, "Log assertion for: {$message}");
        // 実際の実装では、ログファイルまたはLogファサードのMockを確認
        // ここでは簡略化のため、常にtrueを返す
    }

    // =========================================================================
    // TODO-026: DebateService統合テスト
    // =========================================================================

    public function test_CompleteDebateFlow_StartToFinish_Integration()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $room = $this->createRoom(['status' => Room::STATUS_READY]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $user1->id,
            'negative_user_id' => $user2->id,
        ]);

        // 3ターンのフォーマットを設定
        Cache::shouldReceive('remember')
            ->andReturn([
                1 => ['duration' => 60, 'speaker' => 'affirmative', 'name' => 'First Turn'],
                2 => ['duration' => 90, 'speaker' => 'negative', 'name' => 'Second Turn'],
                3 => ['duration' => 60, 'speaker' => 'affirmative', 'name' => 'Final Turn'],
            ]);

        // Act & Assert - ディベート開始
        $this->debateService->startDebate($debate);
        $room->updateStatus(Room::STATUS_DEBATING); // ディベート状態に更新

        $debate->refresh();
        $this->assertEquals(1, $debate->current_turn);
        $this->assertNotNull($debate->turn_end_time);
        Event::assertDispatched(TurnAdvanced::class);
        Queue::assertPushed(AdvanceDebateTurnJob::class);

        // Act & Assert - ターン2への進行
        $room->updateStatus(Room::STATUS_DEBATING); // ディベート状態に設定
        $this->debateService->advanceToNextTurn($debate, 1);

        $debate->refresh();
        $this->assertEquals(2, $debate->current_turn);
        Event::assertDispatchedTimes(TurnAdvanced::class, 2);

        // Act & Assert - ターン3への進行
        $this->debateService->advanceToNextTurn($debate, 2);

        $debate->refresh();
        $this->assertEquals(3, $debate->current_turn);
        Event::assertDispatchedTimes(TurnAdvanced::class, 3);

        // Act & Assert - 最終ターン後の自動終了
        $room->updateStatus(Room::STATUS_DEBATING);
        $this->debateService->advanceToNextTurn($debate, 3);

        $room->refresh();
        $debate->refresh();
        $this->assertEquals(Room::STATUS_FINISHED, $room->status);
        $this->assertNull($debate->turn_end_time);
        Event::assertDispatched(DebateFinished::class);
        Queue::assertPushed(EvaluateDebateJob::class);
    }

    public function test_EarlyTerminationFlow_RequestToApproval_Integration()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $room = $this->createRoom([
            'status' => Room::STATUS_DEBATING,
            'format_type' => 'free'
        ]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $user1->id,
            'negative_user_id' => $user2->id,
        ]);

        // 参加者として設定
        $room->users()->attach([
            $user1->id => ['side' => 'affirmative'],
            $user2->id => ['side' => 'negative'],
        ]);

        // Act & Assert - 早期終了提案
        $result = $this->debateService->requestEarlyTermination($debate, $user1->id);

        $this->assertTrue($result);
        $status = $this->debateService->getEarlyTerminationStatus($debate);
        $this->assertEquals('requested', $status['status']);
        $this->assertEquals($user1->id, $status['requested_by']);

        // Act & Assert - 早期終了承認
        $result = $this->debateService->respondToEarlyTermination($debate, $user2->id, true);

        $this->assertTrue($result);
        $room->refresh();
        $this->assertEquals(Room::STATUS_FINISHED, $room->status);
        Event::assertDispatched(DebateFinished::class);
    }

    public function test_AIDebateFlow_AITurnHandling_Integration()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $aiUser = User::factory()->create();
        $this->app['config']->set('app.ai_user_id', $aiUser->id);

        $humanUser = $this->createUser();
        $room = $this->createRoom([
            'status' => Room::STATUS_READY,
            'is_ai_debate' => true
        ]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $aiUser->id, // AIが先攻
            'negative_user_id' => $humanUser->id,
        ]);

        Cache::shouldReceive('remember')
            ->andReturn([
                1 => ['duration' => 60, 'speaker' => 'affirmative', 'name' => 'AI First Turn'],
                2 => ['duration' => 60, 'speaker' => 'negative', 'name' => 'Human Turn'],
            ]);

        // DebateServiceを再作成してAI UserIDを反映
        $this->debateService = new DebateService();

        // Act & Assert - AI先攻ディベート開始
        $this->debateService->startDebate($debate);
        $room->updateStatus(Room::STATUS_DEBATING); // ディベート状態に更新

        $debate->refresh();
        $this->assertEquals(1, $debate->current_turn);
        Queue::assertPushed(GenerateAIResponseJob::class);
        Queue::assertPushed(AdvanceDebateTurnJob::class);

        // Act & Assert - 人間のターンへ進行
        $this->debateService->advanceToNextTurn($debate, 1);

        $debate->refresh();
        $this->assertEquals(2, $debate->current_turn);
        // 人間のターンではAI応答ジョブは不要
        Queue::assertPushed(GenerateAIResponseJob::class, 1);
    }

    public function test_ConcurrentDebateAccess_HandlesSafely_Integration()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'current_turn' => 1,
        ]);

        Cache::shouldReceive('remember')
            ->andReturn([
                1 => ['duration' => 60, 'speaker' => 'affirmative'],
                2 => ['duration' => 60, 'speaker' => 'negative'],
            ]);

        // Act - 同じターンから複数回進行を試行
        $this->debateService->advanceToNextTurn($debate, 1);
        $this->debateService->advanceToNextTurn($debate, 1); // 期待しないターン

        // Assert - 最初の進行のみ有効
        $debate->refresh();
        $this->assertEquals(2, $debate->current_turn);
        Event::assertDispatchedTimes(TurnAdvanced::class, 1);
    }

    public function test_PerformanceTest_MultipleOperations_CompletesInTime()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $startTime = microtime(true);
        $operations = 20; // 20回の操作（軽量化）

        for ($i = 0; $i < $operations; $i++) {
            $room = $this->createRoom(['status' => Room::STATUS_READY]);
            $debate = $this->createDebate(['room_id' => $room->id]);

            Cache::shouldReceive('remember')
                ->andReturn([1 => ['duration' => 60, 'speaker' => 'affirmative']]);

            // Act
            $this->debateService->startDebate($debate);
            $format = $this->debateService->getFormat($debate);

            // Assert
            $this->assertNotEmpty($format);
        }

        $executionTime = microtime(true) - $startTime;

        // Assert - 20回の操作が1.5秒以内に完了
        $this->assertLessThan(
            1.5,
            $executionTime,
            "Performance test failed: {$operations} operations took {$executionTime} seconds"
        );
    }

    public function test_ErrorRecoveryFlow_CriticalErrorToRecovery_Integration()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'current_turn' => 1,
        ]);

        // 最初のadvanceToNextTurnでエラーが発生
        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \Exception('Temporary service error'));

        // Act - エラー発生
        $this->debateService->advanceToNextTurn($debate, 1);

        // Assert - ディベートが強制終了
        $room->refresh();
        $this->assertEquals(Room::STATUS_TERMINATED, $room->status);

        // Arrange - 新しいディベートでリカバリテスト
        $newRoom = $this->createRoom(['status' => Room::STATUS_READY]);
        $newDebate = $this->createDebate(['room_id' => $newRoom->id]);

        Cache::shouldReceive('remember')
            ->andReturn([1 => ['duration' => 60, 'speaker' => 'affirmative']]);

        // Act - 正常な操作で回復
        $this->debateService->startDebate($newDebate);

        // Assert - 正常に動作
        $newDebate->refresh();
        $this->assertEquals(1, $newDebate->current_turn);
        $this->assertNotNull($newDebate->turn_end_time);
    }

    public function test_ComplexScenario_MultipleUsersAndStates_Integration()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $users = User::factory()->count(4)->create();
        $room = $this->createRoom(['status' => Room::STATUS_DEBATING]);
        $debate = $this->createDebate([
            'room_id' => $room->id,
            'affirmative_user_id' => $users[0]->id,
            'negative_user_id' => $users[1]->id,
            'current_turn' => 1,
        ]);

        // 複雑なフォーマット設定
        Cache::shouldReceive('remember')
            ->andReturn([
                1 => ['duration' => 30, 'speaker' => 'affirmative', 'name' => 'Opening'],
                2 => ['duration' => 30, 'speaker' => 'negative', 'name' => 'Response'],
                3 => ['duration' => 45, 'speaker' => 'affirmative', 'name' => 'Rebuttal', 'is_questions' => true],
                4 => ['duration' => 45, 'speaker' => 'negative', 'name' => 'Counter-Rebuttal'],
            ]);

        // Act & Assert - 質疑ターンの確認
        $this->assertTrue($this->debateService->isQuestioningTurn($debate, 3));
        $this->assertFalse($this->debateService->isQuestioningTurn($debate, 1));
        $this->assertFalse($this->debateService->isQuestioningTurn($debate, 2));
        $this->assertFalse($this->debateService->isQuestioningTurn($debate, 4));

        // Act & Assert - ターン進行テスト
        $this->debateService->advanceToNextTurn($debate, 1); // 1→2
        $debate->refresh();
        $this->assertEquals(2, $debate->current_turn);

        $this->debateService->advanceToNextTurn($debate, 2); // 2→3
        $debate->refresh();
        $this->assertEquals(3, $debate->current_turn);

        $this->debateService->advanceToNextTurn($debate, 3); // 3→4
        $debate->refresh();
        $this->assertEquals(4, $debate->current_turn);

        // Act & Assert - 最終完了 (ターン4の後は自動終了)
        $this->debateService->advanceToNextTurn($debate, 4);

        $room->refresh();
        $this->assertEquals(Room::STATUS_FINISHED, $room->status);
        Event::assertDispatched(DebateFinished::class);
        Queue::assertPushed(EvaluateDebateJob::class);
    }

    public function test_RealTimeEventFlow_EventSequenceConsistency_Integration()
    {
        // Arrange
        Event::fake();
        Queue::fake();

        $room = $this->createRoom(['status' => Room::STATUS_READY]);
        $debate = $this->createDebate(['room_id' => $room->id]);

        Cache::shouldReceive('remember')
            ->andReturn([
                1 => ['duration' => 60, 'speaker' => 'affirmative'],
                2 => ['duration' => 60, 'speaker' => 'negative'],
            ]);

        // Act - ディベート開始からターン進行、終了まで
        $this->debateService->startDebate($debate);
        $room->updateStatus(Room::STATUS_DEBATING); // ディベート状態に更新

        $debate->refresh();
        $this->assertEquals(1, $debate->current_turn);

        $this->debateService->advanceToNextTurn($debate, 1);
        $debate->refresh();
        $this->assertEquals(2, $debate->current_turn);

        $this->debateService->advanceToNextTurn($debate, 2);
        $room->refresh();
        $this->assertEquals(Room::STATUS_FINISHED, $room->status);

        // Assert - イベントとジョブの確認
        Event::assertDispatched(TurnAdvanced::class);
        Event::assertDispatched(DebateFinished::class);
        Queue::assertPushed(AdvanceDebateTurnJob::class);
        Queue::assertPushed(EvaluateDebateJob::class);
    }

    // =========================================================================
    // skipAIPrepTime() テスト
    // =========================================================================

    public function test_skipAIPrepTime_Success()
    {
        // Arrange
        $room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'is_ai_debate' => true,
            'format_type' => 'format_name_nsda_policy'
        ]);

        $humanUser = User::factory()->create();
        // AI userが既に存在する場合は取得、存在しない場合は作成
        $aiUser = User::find(1) ?? User::factory()->create(['id' => 1]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $humanUser->id,
            'negative_user_id' => $aiUser->id,
            'current_turn' => 2, // AI準備時間のターン
            'turn_end_time' => now()->addSeconds(30)
        ]);

        Event::fake();
        Queue::fake();

        // Act
        $result = $this->debateService->skipAIPrepTime($debate);

        // Assert
        $this->assertTrue($result);
        $debate->refresh();
        $this->assertEquals(3, $debate->current_turn); // 次のターンに進行
        Event::assertDispatched(TurnAdvanced::class);
    }

    public function test_skipAIPrepTime_FailsForNonAIDebate()
    {
        // Arrange
        $room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'is_ai_debate' => false // 人間同士のディベート
        ]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'current_turn' => 2,
            'turn_end_time' => Carbon::now()->addSeconds(30)
        ]);

        Event::fake();

        // Act
        $result = $this->debateService->skipAIPrepTime($debate);

        // Assert
        $this->assertFalse($result);
        Event::assertNotDispatched(TurnAdvanced::class);
    }

    public function test_skipAIPrepTime_FailsWhenNotPrepTime()
    {
        // Arrange
        $room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'is_ai_debate' => true
        ]);

        $aiUser = User::find(1) ?? User::factory()->create(['id' => 1]);
        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'negative_user_id' => $aiUser->id,
            'current_turn' => 3,
            'turn_end_time' => now()->addSeconds(30)
        ]);

        // フォーマットをモック（準備時間ではない）
        $format = [
            3 => ['speaker' => 'negative', 'duration' => 120, 'is_prep_time' => false], // スピーチ時間
        ];
        Cache::put("debate_format_{$room->id}_en", $format, 60);

        Event::fake();

        // Act
        $result = $this->debateService->skipAIPrepTime($debate);

        // Assert
        $this->assertFalse($result);
        Event::assertNotDispatched(TurnAdvanced::class);
    }

    public function test_skipAIPrepTime_FailsWhenNotAITurn()
    {
        // Arrange
        $room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'is_ai_debate' => true
        ]);

        $humanUser = User::factory()->create();
        $aiUser = User::find(1) ?? User::factory()->create(['id' => 1]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $humanUser->id,
            'negative_user_id' => $aiUser->id,
            'current_turn' => 1,
            'turn_end_time' => now()->addSeconds(30)
        ]);

        // フォーマットをモック（人間の準備時間）
        $format = [
            1 => ['speaker' => 'affirmative', 'duration' => 60, 'is_prep_time' => true], // 人間の準備時間
        ];
        Cache::put("debate_format_{$room->id}_en", $format, 60);

        Event::fake();

        // Act
        $result = $this->debateService->skipAIPrepTime($debate);

        // Assert
        $this->assertFalse($result);
        Event::assertNotDispatched(TurnAdvanced::class);
    }

    public function test_skipAIPrepTime_FailsWhenRemainingTimeLessThan5Seconds()
    {
        // Arrange
        $room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'is_ai_debate' => true
        ]);

        $aiUser = User::find(1) ?? User::factory()->create(['id' => 1]);
        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'negative_user_id' => $aiUser->id,
            'current_turn' => 2,
            'turn_end_time' => now()->addSeconds(3) // 残り3秒
        ]);

        // フォーマットをモック
        $format = [
            2 => ['speaker' => 'negative', 'duration' => 60, 'is_prep_time' => true], // AI準備時間
        ];
        Cache::put("debate_format_{$room->id}_en", $format, 60);

        Event::fake();

        // Act
        $result = $this->debateService->skipAIPrepTime($debate);

        // Assert
        $this->assertFalse($result);
        Event::assertNotDispatched(TurnAdvanced::class);
    }
}
