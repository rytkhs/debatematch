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
}
