<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use App\Models\DebateMessage;
use App\Jobs\AdvanceDebateTurnJob;
use App\Jobs\GenerateAIResponseJob;
use App\Events\TurnAdvanced;
use App\Events\DebateStarted;
use App\Services\DebateService;
use App\Livewire\Debates\Header;
use Carbon\Carbon;
use Livewire\Livewire;

use Tests\Helpers\LivewireTestHelpers;

/**
 * AI準備時間スキップ機能の統合テスト
 *
 * AIディベート開始から準備時間スキップまでの完全なフローをテスト
 * スキップ後のターン進行とイベントブロードキャストをテスト
 * 自動進行との競合状態を適切に処理することをテスト
 *
 * Requirements: 1.1, 1.2, 1.3, 1.4, 3.1, 3.2, 3.3, 3.4
 */
class AIDebateSkipIntegrationTest extends TestCase
{
    use RefreshDatabase, LivewireTestHelpers;

    private User $humanUser;
    private User $aiUser;
    private DebateService $debateService;

    protected function setUp(): void
    {
        parent::setUp();

        // キューを同期実行に設定
        Config::set('queue.default', 'sync');

        // AIユーザーを作成
        $this->aiUser = User::factory()->create();
        Config::set('app.ai_user_id', $this->aiUser->id);

        // 人間ユーザーを作成
        $this->humanUser = User::factory()->create([
            'name' => 'Human User',
            'email' => 'human@test.com'
        ]);

        $this->debateService = new DebateService();

        // 外部APIのモック設定
        $this->mockExternalAPIs();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * メインテスト: AIディベート開始から準備時間スキップまでの完全なフロー
     *
     * Requirements: 1.1, 1.2, 1.3, 1.4, 3.1, 3.2, 3.3
     */
    public function test_complete_ai_debate_skip_flow_from_start_to_skip(): void
    {
        Event::fake();
        Queue::fake();

        // ステップ1: AIディベートルームを作成
        $room = $this->createAIDebateRoom();
        $this->assertAIDebateRoomCreatedCorrectly($room);

        // ステップ2: ディベートを開始
        $debate = $this->startAIDebate($room);
        $this->assertAIDebateStartedCorrectly($debate);

        // ステップ3: AI準備時間まで進行
        $this->advanceToAIPrepTime($debate);
        $this->assertInAIPrepTime($debate);

        // ステップ4: スキップボタンの表示確認
        $this->assertSkipButtonVisible($debate);

        // ステップ5: AI準備時間をスキップ
        $this->skipAIPrepTime($debate);
        $this->assertSkipSuccessful($debate);

        // ステップ6: ターン進行の確認
        $this->assertTurnAdvancedAfterSkip($debate);

        // ステップ7: イベントブロードキャストの確認
        $this->assertEventsDispatchedAfterSkip();
    }

    /**
     * スキップ後のターン進行とイベントブロードキャストのテスト
     *
     * Requirements: 3.1, 3.2, 3.3
     */
    public function test_skip_triggers_correct_turn_advancement_and_events(): void
    {
        Event::fake();
        Queue::fake();

        // AI準備時間中のディベートを作成
        $debate = $this->createDebateInAIPrepTime();
        $initialTurn = $debate->current_turn;

        // スキップを実行
        $result = $this->debateService->skipAIPrepTime($debate);

        // スキップが成功することを確認
        $this->assertTrue($result);

        // ターンが進行していることを確認
        $debate->refresh();
        $this->assertEquals($initialTurn + 1, $debate->current_turn);

        // 適切なイベントが発火されることを確認
        Event::assertDispatched(TurnAdvanced::class, function ($event) use ($debate) {
            return $event->debate->id === $debate->id &&
                   $event->additionalData['current_turn'] === $debate->current_turn;
        });

        // 次のターンのジョブがスケジュールされることを確認
        Queue::assertPushed(AdvanceDebateTurnJob::class, function ($job) use ($debate) {
            return $job->debateId === $debate->id &&
                   $job->expectedTurn === $debate->current_turn;
        });

        // 次のターンがAIターンの場合、AI応答ジョブも発火されることを確認
        if ($this->isNextTurnAI($debate)) {
            Queue::assertPushed(GenerateAIResponseJob::class, function ($job) use ($debate) {
                return $job->debateId === $debate->id &&
                       $job->currentTurn === $debate->current_turn;
            });
        }
    }

    /**
     * 自動進行との競合状態を適切に処理することをテスト
     *
     * Requirements: 3.4
     */
    public function test_skip_handles_race_condition_with_automatic_advancement(): void
    {
        Event::fake();
        Queue::fake();

        // AI準備時間中のディベートを作成
        $debate = $this->createDebateInAIPrepTime();
        $initialTurn = $debate->current_turn;

        // 同時に自動進行とスキップが発生する状況をシミュレート
        // 1. 自動進行ジョブを実行（ターンを進める）
        $this->debateService->advanceToNextTurn($debate, $initialTurn);

        // 2. その後でスキップを試行（既にターンが進んでいるため失敗するはず）
        $result = $this->debateService->skipAIPrepTime($debate);

        // スキップが失敗することを確認（既にターンが進んでいるため）
        $this->assertFalse($result);

        // ターンは自動進行によって進んでいることを確認
        $debate->refresh();
        $this->assertEquals($initialTurn + 1, $debate->current_turn);
    }

    /**
     * 複数回のスキップボタンクリックに対する保護のテスト
     *
     * Requirements: 3.4
     */
    public function test_skip_prevents_multiple_simultaneous_clicks(): void
    {
        Event::fake();
        Queue::fake();

        // AI準備時間中のディベートを作成
        $debate = $this->createDebateInAIPrepTime();
        $initialTurn = $debate->current_turn;

        // 複数回のスキップを同時に実行
        $result1 = $this->debateService->skipAIPrepTime($debate);
        $result2 = $this->debateService->skipAIPrepTime($debate);

        // 最初のスキップのみ成功することを確認
        $this->assertTrue($result1);
        $this->assertFalse($result2);

        // ターンは1回だけ進行することを確認
        $debate->refresh();
        $this->assertEquals($initialTurn + 1, $debate->current_turn);

        // イベントも1回だけ発火されることを確認
        Event::assertDispatchedTimes(TurnAdvanced::class, 1);
    }

    /**
     * 残り時間が少ない場合のスキップ拒否テスト
     *
     * Requirements: 1.4
     */
    public function test_skip_is_rejected_when_remaining_time_is_low(): void
    {
        Event::fake();
        Queue::fake();

        // 残り時間が少ないAI準備時間中のディベートを作成
        $debate = $this->createDebateInAIPrepTimeWithLittleTime();
        $initialTurn = $debate->current_turn;

        // スキップを試行
        $result = $this->debateService->skipAIPrepTime($debate);

        // スキップが拒否されることを確認
        $this->assertFalse($result);

        // ターンが進行していないことを確認
        $debate->refresh();
        $this->assertEquals($initialTurn, $debate->current_turn);

        // イベントが発火されていないことを確認
        Event::assertNotDispatched(TurnAdvanced::class);
    }

    /**
     * 非AIディベートでのスキップ拒否テスト
     *
     * Requirements: 1.2
     */
    public function test_skip_is_rejected_in_non_ai_debate(): void
    {
        Event::fake();
        Queue::fake();

        // 人間同士のディベートを作成
        $debate = $this->createHumanDebateInPrepTime();
        $initialTurn = $debate->current_turn;

        // スキップを試行
        $result = $this->debateService->skipAIPrepTime($debate);

        // スキップが拒否されることを確認
        $this->assertFalse($result);

        // ターンが進行していないことを確認
        $debate->refresh();
        $this->assertEquals($initialTurn, $debate->current_turn);

        // イベントが発火されていないことを確認
        Event::assertNotDispatched(TurnAdvanced::class);
    }

    /**
     * 準備時間以外でのスキップ拒否テスト
     *
     * Requirements: 1.3
     */
    public function test_skip_is_rejected_when_not_prep_time(): void
    {
        Event::fake();
        Queue::fake();

        // AIのスピーチ時間中のディベートを作成
        $debate = $this->createDebateInAISpeechTime();
        $initialTurn = $debate->current_turn;

        // スキップを試行
        $result = $this->debateService->skipAIPrepTime($debate);

        // スキップが拒否されることを確認
        $this->assertFalse($result);

        // ターンが進行していないことを確認
        $debate->refresh();
        $this->assertEquals($initialTurn, $debate->current_turn);

        // イベントが発火されていないことを確認
        Event::assertNotDispatched(TurnAdvanced::class);
    }

    /**
     * Livewireコンポーネントとの統合テスト
     *
     * Requirements: 2.1, 2.2, 2.4, 4.1, 4.2
     */
    public function test_livewire_component_integration_with_skip_functionality(): void
    {
        // AI準備時間中のディベートを作成
        $debate = $this->createDebateInAIPrepTime();

        // ユーザーとしてログイン
        $this->actingAs($this->humanUser);

        // Livewireコンポーネントをテスト
        $component = Livewire::test(Header::class, ['debate' => $debate]);

        // スキップボタンが表示されることを確認
        $component->assertSet('canSkipAIPrepTime', true)
                  ->assertSee(__('ai_debate.skip_prep_time'));

        // スキップボタンをクリック
        $component->call('skipAIPrepTime')
                  ->assertDispatched('showFlashMessage', __('ai_debate.prep_time_skipped'), 'success');

        // ターンが進行していることを確認
        $debate->refresh();
        $this->assertGreaterThan(1, $debate->current_turn);
    }

    /**
     * エラー処理の統合テスト
     *
     * Requirements: 3.4
     */
    public function test_error_handling_integration(): void
    {
        // 人間のターン中のディベートを作成
        $debate = $this->createDebateInHumanTurn();

        // ユーザーとしてログイン
        $this->actingAs($this->humanUser);

        // Livewireコンポーネントをテスト
        $component = Livewire::test(Header::class, ['debate' => $debate]);

        // スキップボタンが非表示であることを確認
        $component->assertSet('canSkipAIPrepTime', false)
                  ->assertDontSee(__('ai_debate.skip_prep_time'));

        // スキップを試行してエラーメッセージが表示されることを確認
        $component->call('skipAIPrepTime')
                  ->assertDispatched('showFlashMessage', __('ai_debate.skip_not_available'), 'error');
    }

    // ========================================================================
    // ヘルパーメソッド: ディベート作成
    // ========================================================================

    private function createAIDebateRoom(): Room
    {
        return Room::factory()->create([
            'name' => 'AI Debate Room',
            'topic' => 'Should AI be regulated?',
            'status' => Room::STATUS_READY,
            'is_ai_debate' => true,
            'language' => 'japanese',
            'format_type' => 'format_name_nada_high',
            'created_by' => $this->humanUser->id,
        ]);
    }

    private function startAIDebate(Room $room): Debate
    {
        // 参加者を追加
        $room->users()->attach($this->humanUser->id, ['side' => 'affirmative']);
        $room->users()->attach($this->aiUser->id, ['side' => 'negative']);

        // ディベートを作成
        $debate = Debate::create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->humanUser->id,
            'negative_user_id' => $this->aiUser->id,
        ]);

        // フォーマットをキャッシュ
        $this->mockDebateFormat();

        // ディベートを開始
        $this->debateService->startDebate($debate);
        $room->updateStatus(Room::STATUS_DEBATING);

        return $debate->fresh();
    }

    private function advanceToAIPrepTime(Debate $debate): void
    {
        // AI準備時間のターン（ターン1）まで進行
        // 既にターン1にいる場合は何もしない
        if ($debate->current_turn === 1) {
            return;
        }

        // ターン1まで進行
        $this->debateService->updateTurn($debate, 1);
    }

    private function createDebateInAIPrepTime(): Debate
    {
        $room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'is_ai_debate' => true,
        ]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->humanUser->id,
            'negative_user_id' => $this->aiUser->id,
            'current_turn' => 1, // AI準備時間のターン
            'turn_end_time' => now()->addMinutes(2)
        ]);

        $this->mockDebateFormat();

        return $debate;
    }

    private function createDebateInAIPrepTimeWithLittleTime(): Debate
    {
        $room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'is_ai_debate' => true,
        ]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->humanUser->id,
            'negative_user_id' => $this->aiUser->id,
            'current_turn' => 1, // AI準備時間のターン
            'turn_end_time' => now()->addSeconds(3) // 3秒後に終了
        ]);

        $this->mockDebateFormat();

        return $debate;
    }

    private function createHumanDebateInPrepTime(): Debate
    {
        $anotherHuman = User::factory()->create();
        $room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'is_ai_debate' => false,
        ]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->humanUser->id,
            'negative_user_id' => $anotherHuman->id,
            'current_turn' => 1, // 準備時間のターン
            'turn_end_time' => now()->addMinutes(2)
        ]);

        $this->mockDebateFormat();

        return $debate;
    }

    private function createDebateInAISpeechTime(): Debate
    {
        $room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'is_ai_debate' => true,
        ]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->humanUser->id,
            'negative_user_id' => $this->aiUser->id,
            'current_turn' => 3, // AIのスピーチ時間のターン
            'turn_end_time' => now()->addMinutes(5)
        ]);

        $this->mockDebateFormat();

        return $debate;
    }

    private function createDebateInHumanTurn(): Debate
    {
        $room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'is_ai_debate' => true,
        ]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->humanUser->id,
            'negative_user_id' => $this->aiUser->id,
            'current_turn' => 2, // 人間のターン
            'turn_end_time' => now()->addMinutes(5)
        ]);

        $this->mockDebateFormat();

        return $debate;
    }

    // ========================================================================
    // ヘルパーメソッド: アクション実行
    // ========================================================================

    private function skipAIPrepTime(Debate $debate): void
    {
        $result = $this->debateService->skipAIPrepTime($debate);
        $this->assertTrue($result, 'AI準備時間のスキップが失敗しました');
    }

    // ========================================================================
    // ヘルパーメソッド: アサーション
    // ========================================================================

    private function assertAIDebateRoomCreatedCorrectly(Room $room): void
    {
        $this->assertNotNull($room);
        $this->assertTrue($room->is_ai_debate);
        $this->assertEquals(Room::STATUS_READY, $room->status);
        $this->assertEquals($this->humanUser->id, $room->created_by);
    }

    private function assertAIDebateStartedCorrectly(Debate $debate): void
    {
        $this->assertNotNull($debate);
        $this->assertEquals(1, $debate->current_turn);
        $this->assertEquals($this->humanUser->id, $debate->affirmative_user_id);
        $this->assertEquals($this->aiUser->id, $debate->negative_user_id);
        $this->assertNotNull($debate->turn_end_time);
        $this->assertEquals(Room::STATUS_DEBATING, $debate->room->status);
    }

    private function assertInAIPrepTime(Debate $debate): void
    {
        $format = $this->debateService->getFormat($debate);
        $currentTurnInfo = $format[$debate->current_turn] ?? null;

        $this->assertNotNull($currentTurnInfo);
        $this->assertTrue($currentTurnInfo['is_prep_time'] ?? false);
        $this->assertEquals('negative', $currentTurnInfo['speaker']); // AIのターン
    }

    private function assertSkipButtonVisible(Debate $debate): void
    {
        $this->actingAs($this->humanUser);

        Livewire::test(Header::class, ['debate' => $debate])
            ->assertSet('canSkipAIPrepTime', true)
            ->assertSee(__('ai_debate.skip_prep_time'));
    }

    private function assertSkipSuccessful(Debate $debate): void
    {
        // スキップ後にターンが進行していることを確認
        $this->assertGreaterThan(1, $debate->fresh()->current_turn);
    }

    private function assertTurnAdvancedAfterSkip(Debate $debate): void
    {
        $debate->refresh();
        $this->assertEquals(2, $debate->current_turn);
        $this->assertNotNull($debate->turn_end_time);
    }

    private function assertEventsDispatchedAfterSkip(): void
    {
        Event::assertDispatched(TurnAdvanced::class);
        Queue::assertPushed(AdvanceDebateTurnJob::class);
    }

    // ========================================================================
    // ヘルパーメソッド: ユーティリティ
    // ========================================================================

    private function isNextTurnAI(Debate $debate): bool
    {
        $format = $this->debateService->getFormat($debate);
        $currentTurnInfo = $format[$debate->current_turn] ?? null;

        if (!$currentTurnInfo) {
            return false;
        }

        $currentSpeakerId = ($currentTurnInfo['speaker'] === 'affirmative')
            ? $debate->affirmative_user_id
            : $debate->negative_user_id;

        return $currentSpeakerId === $this->aiUser->id;
    }

    private function mockDebateFormat(): void
    {
        Cache::shouldReceive('remember')
            ->withAnyArgs()
            ->andReturn($this->getTestFormat());
    }

    private function mockExternalAPIs(): void
    {
        // OpenRouter APIのモック
        \Illuminate\Support\Facades\Http::fake([
            'openrouter.ai/*' => \Illuminate\Support\Facades\Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Mock AI response for debate turn.'
                        ]
                    ]
                ]
            ], 200)
        ]);
    }

    private function getTestFormat(): array
    {
        return [
            1 => [
                'name' => 'AI Preparation Time',
                'speaker' => 'negative', // AIのターン
                'is_prep_time' => true,
                'duration' => 120
            ],
            2 => [
                'name' => 'Affirmative Opening',
                'speaker' => 'affirmative', // 人間のターン
                'is_prep_time' => false,
                'duration' => 300
            ],
            3 => [
                'name' => 'Negative Opening',
                'speaker' => 'negative', // AIのターン
                'is_prep_time' => false,
                'duration' => 300
            ],
            4 => [
                'name' => 'Affirmative Rebuttal',
                'speaker' => 'affirmative', // 人間のターン
                'is_prep_time' => false,
                'duration' => 240
            ]
        ];
    }
}
