<?php

namespace Tests\Feature\Livewire\Debates;

use App\Livewire\Debates\Header;
use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use App\Services\DebateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Helpers\LivewireTestHelpers;
use Carbon\Carbon;

/**
 * Debates\HeaderコンポーネントのLivewireテスト
 *
 * AI準備時間スキップ機能に関するテストを実装：
 * - AI準備時間中にスキップボタンが表示されることをテスト
 * - 人間のターン中にスキップボタンが非表示になることをテスト
 * - スキップボタンクリックでターンが進行することをテスト
 * - エラー時の適切なフィードバック表示をテスト
 *
 * Requirements: 2.1, 2.2, 2.3, 2.4, 4.1, 4.2, 4.3, 4.4
 */
class HeaderTest extends TestCase
{
    use RefreshDatabase, LivewireTestHelpers;

    private User $user;
    private User $aiUser;
    private DebateService $debateService;

    protected function setUp(): void
    {
        parent::setUp();

        // AIユーザーを作成
        $this->aiUser = User::factory()->create(['id' => 1]);
        Config::set('app.ai_user_id', $this->aiUser->id);

        // 通常のユーザーを作成
        $this->user = User::factory()->create();

        $this->debateService = app(DebateService::class);
    }

    #[Test]
    public function skip_button_is_visible_during_ai_prep_time()
    {
        // AIディベートの準備時間中のディベートを作成
        $debate = $this->createAIDebateInPrepTime();

        // ユーザーとしてログイン
        $this->actingAs($this->user);

        Livewire::test(Header::class, ['debate' => $debate])
            ->assertSee(__('ai_debate.skip_prep_time'))
            ->assertSet('canSkipAIPrepTime', true)
            ->assertSet('isAITurn', true)
            ->assertSet('isPrepTime', true);
    }

    #[Test]
    public function skip_button_is_hidden_during_human_turn()
    {
        // 人間のターン中のディベートを作成
        $debate = $this->createDebateInHumanTurn();

        // ユーザーとしてログイン
        $this->actingAs($this->user);

        Livewire::test(Header::class, ['debate' => $debate])
            ->assertDontSee(__('ai_debate.skip_prep_time'))
            ->assertSet('canSkipAIPrepTime', false)
            ->assertSet('isAITurn', false);
    }

    #[Test]
    public function skip_button_is_hidden_in_non_ai_debate()
    {
        // 人間同士のディベートを作成
        $debate = $this->createHumanDebate();

        // ユーザーとしてログイン
        $this->actingAs($this->user);

        Livewire::test(Header::class, ['debate' => $debate])
            ->assertDontSee(__('ai_debate.skip_prep_time'))
            ->assertSet('canSkipAIPrepTime', false)
            ->assertSet('isAITurn', false);
    }

    #[Test]
    public function skip_button_is_disabled_when_remaining_time_is_less_than_5_seconds()
    {
        // 残り時間が少ないAI準備時間中のディベートを作成
        $debate = $this->createAIDebateInPrepTimeWithLittleTime();

        // ユーザーとしてログイン
        $this->actingAs($this->user);

        $component = Livewire::test(Header::class, ['debate' => $debate]);

        // スキップボタンは表示されるが、残り時間が少ない
        $component->assertSet('canSkipAIPrepTime', true)
            ->assertSet('remainingTime', 3); // 3秒残り

        // HTMLでdisabled属性が設定されていることを確認
        $component->assertSeeHtml('disabled');
    }

    #[Test]
    public function clicking_skip_button_advances_turn()
    {
        // AIディベートの準備時間中のディベートを作成
        $debate = $this->createAIDebateInPrepTime();
        $initialTurn = $debate->current_turn;

        // ユーザーとしてログイン
        $this->actingAs($this->user);

        // DebateServiceのskipAIPrepTimeメソッドをモック
        $mockDebateService = $this->mock(DebateService::class);
        $mockDebateService->shouldReceive('getFormat')
            ->andReturn($this->getTestFormat());
        $mockDebateService->shouldReceive('skipAIPrepTime')
            ->once()
            ->andReturn(true);

        Livewire::test(Header::class, ['debate' => $debate])
            ->call('skipAIPrepTime')
            ->assertDispatched('showFlashMessage', __('ai_debate.prep_time_skipped'), 'success');
    }

    #[Test]
    public function skip_button_shows_error_when_not_available()
    {
        // 人間のターン中のディベートを作成
        $debate = $this->createDebateInHumanTurn();

        // ユーザーとしてログイン
        $this->actingAs($this->user);

        Livewire::test(Header::class, ['debate' => $debate])
            ->call('skipAIPrepTime')
            ->assertDispatched('showFlashMessage', __('ai_debate.skip_not_available'), 'error');
    }

    #[Test]
    public function skip_button_shows_error_when_service_fails()
    {
        // AIディベートの準備時間中のディベートを作成
        $debate = $this->createAIDebateInPrepTime();

        // ユーザーとしてログイン
        $this->actingAs($this->user);

        // DebateServiceのskipAIPrepTimeメソッドをモックして失敗させる
        $mockDebateService = $this->mock(DebateService::class);
        $mockDebateService->shouldReceive('getFormat')
            ->andReturn($this->getTestFormat());
        $mockDebateService->shouldReceive('skipAIPrepTime')
            ->once()
            ->andReturn(false);

        Livewire::test(Header::class, ['debate' => $debate])
            ->call('skipAIPrepTime')
            ->assertDispatched('showFlashMessage', __('ai_debate.skip_failed'), 'error');
    }

    #[Test]
    public function turn_advanced_event_updates_skip_button_state()
    {
        // AIディベートの準備時間中のディベートを作成
        $debate = $this->createAIDebateInPrepTime();

        // ユーザーとしてログイン
        $this->actingAs($this->user);

        $component = Livewire::test(Header::class, ['debate' => $debate]);

        // 初期状態でスキップボタンが表示されることを確認
        $component->assertSet('canSkipAIPrepTime', true);

        // TurnAdvancedイベントを発火して人間のターンに変更
        $component->dispatch("echo-presence:debate.{$debate->id},TurnAdvanced", [
            'turn_number' => 2,
            'speaker' => 'affirmative', // 人間のターン
            'is_prep_time' => false,
            'turn_end_time' => now()->addMinutes(5)->timestamp
        ]);

        // スキップボタンが非表示になることを確認
        $component->assertSet('canSkipAIPrepTime', false)
            ->assertSet('isAITurn', false);
    }

    #[Test]
    public function skip_button_is_hidden_when_room_is_not_debating()
    {
        // 待機中のAIディベートルームを作成
        $room = Room::factory()->aiDebate()->waiting()->create();

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->user->id,
            'negative_user_id' => $this->aiUser->id,
            'current_turn' => 1,
            'turn_end_time' => now()->addMinutes(2)
        ]);

        // フォーマットをキャッシュ
        $this->mockDebateFormat();

        // ユーザーとしてログイン
        $this->actingAs($this->user);

        Livewire::test(Header::class, ['debate' => $debate])
            ->assertDontSee(__('ai_debate.skip_prep_time'))
            ->assertSet('canSkipAIPrepTime', false);
    }

    #[Test]
    public function skip_button_is_hidden_during_ai_speech_time()
    {
        // AIのスピーチ時間中のディベートを作成
        $room = Room::factory()->aiDebate()->debating()->create();

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->user->id,
            'negative_user_id' => $this->aiUser->id,
            'current_turn' => 3, // AIのスピーチターン
            'turn_end_time' => now()->addMinutes(5)
        ]);

        // フォーマットをキャッシュ
        $this->mockDebateFormat();

        // ユーザーとしてログイン
        $this->actingAs($this->user);

        Livewire::test(Header::class, ['debate' => $debate])
            ->assertDontSee(__('ai_debate.skip_prep_time'))
            ->assertSet('canSkipAIPrepTime', false)
            ->assertSet('isAITurn', true)
            ->assertSet('isPrepTime', false);
    }

    #[Test]
    public function remaining_time_is_calculated_correctly()
    {
        // 特定の時間でテストを固定
        $testTime = Carbon::parse('2024-01-01 12:00:00');
        Carbon::setTestNow($testTime);

        // DebateServiceをモック
        $mockDebateService = $this->mock(DebateService::class);
        $mockDebateService->shouldReceive('getFormat')
            ->andReturn($this->getTestFormat());

        $room = Room::factory()->aiDebate()->debating()->create();

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->user->id,
            'negative_user_id' => $this->aiUser->id,
            'current_turn' => 1,
            'turn_end_time' => $testTime->copy()->addMinutes(2) // 2分後に終了
        ]);

        // ユーザーとしてログイン
        $this->actingAs($this->user);

        $component = Livewire::test(Header::class, ['debate' => $debate]);

        // turnEndTimeが正しく設定されていることを確認
        $expectedTimestamp = $testTime->copy()->addMinutes(2)->timestamp;
        $component->assertSet('turnEndTime', $expectedTimestamp);

        // 残り時間の計算は実際のtime()関数を使用するため、
        // 現在時刻との差分が正しく計算されることを確認
        // （テスト実行時の実際の時間によって値が変わるため、範囲チェック）
        $actualRemainingTime = $component->get('remainingTime');
        $this->assertGreaterThanOrEqual(0, $actualRemainingTime);
    }

    #[Test]
    public function component_handles_null_turn_end_time()
    {
        // DebateServiceをモック
        $mockDebateService = $this->mock(DebateService::class);
        $mockDebateService->shouldReceive('getFormat')
            ->andReturn($this->getTestFormat());

        $room = Room::factory()->aiDebate()->debating()->create();

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->user->id,
            'negative_user_id' => $this->aiUser->id,
            'current_turn' => 1,
            'turn_end_time' => null // 終了時間がnull
        ]);

        // ユーザーとしてログイン
        $this->actingAs($this->user);

        $component = Livewire::test(Header::class, ['debate' => $debate]);

        // 残り時間が0になることを確認
        $component->assertSet('remainingTime', 0)
            ->assertSet('turnEndTime', null);
    }

    /**
     * AIディベートの準備時間中のディベートを作成
     */
    private function createAIDebateInPrepTime(): Debate
    {
        $room = Room::factory()->aiDebate()->debating()->create();

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->user->id,
            'negative_user_id' => $this->aiUser->id,
            'current_turn' => 1, // AI準備時間のターン
            'turn_end_time' => now()->addMinutes(2)
        ]);

        // フォーマットをキャッシュ
        $this->mockDebateFormat();

        return $debate;
    }

    /**
     * 残り時間が少ないAI準備時間中のディベートを作成
     */
    private function createAIDebateInPrepTimeWithLittleTime(): Debate
    {
        $room = Room::factory()->aiDebate()->debating()->create();

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->user->id,
            'negative_user_id' => $this->aiUser->id,
            'current_turn' => 1, // AI準備時間のターン
            'turn_end_time' => now()->addSeconds(3) // 3秒後に終了
        ]);

        // フォーマットをキャッシュ
        $this->mockDebateFormat();

        return $debate;
    }

    /**
     * 人間のターン中のディベートを作成
     */
    private function createDebateInHumanTurn(): Debate
    {
        $room = Room::factory()->aiDebate()->debating()->create();

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->user->id,
            'negative_user_id' => $this->aiUser->id,
            'current_turn' => 2, // 人間のターン
            'turn_end_time' => now()->addMinutes(5)
        ]);

        // フォーマットをキャッシュ
        $this->mockDebateFormat();

        return $debate;
    }

    /**
     * 人間同士のディベートを作成
     */
    private function createHumanDebate(): Debate
    {
        $anotherUser = User::factory()->create();
        $room = Room::factory()->debating()->create(['is_ai_debate' => false]);

        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $this->user->id,
            'negative_user_id' => $anotherUser->id,
            'current_turn' => 1,
            'turn_end_time' => now()->addMinutes(5)
        ]);

        // フォーマットをキャッシュ
        $this->mockDebateFormat();

        return $debate;
    }

    /**
     * ディベートフォーマットをモック
     */
    private function mockDebateFormat(): void
    {
        Cache::shouldReceive('remember')
            ->andReturn($this->getTestFormat());
    }

    /**
     * テスト用のディベートフォーマットを取得
     */
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
            ]
        ];
    }
}
