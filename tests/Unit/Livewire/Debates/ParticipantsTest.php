<?php

namespace Tests\Unit\Livewire\Debates;

use Tests\Unit\Livewire\BaseLivewireTest;
use App\Livewire\Debates\Participants;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use App\Services\DebateService;
use Livewire\Livewire;
use Tests\Helpers\MockHelpers;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Auth;

class ParticipantsTest extends BaseLivewireTest
{
    protected User $affirmativeUser;
    protected User $negativeUser;
    protected Room $room;
    protected Debate $debate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDebateData();
        MockHelpers::mockDebateConfigs();
        MockHelpers::mockAIConfigs();
    }

    private function setupDebateData(): void
    {
        $this->affirmativeUser = User::factory()->create();
        $this->negativeUser = User::factory()->create();

        $this->room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'created_by' => $this->affirmativeUser->id,
        ]);

        $this->debate = Debate::factory()->create([
            'room_id' => $this->room->id,
            'affirmative_user_id' => $this->affirmativeUser->id,
            'negative_user_id' => $this->negativeUser->id,
            'current_turn' => 0,
        ]);
    }

    /**
     * TODO-051: Debates/Participants基本機能テスト - コンポーネント初期化
     */
    #[Test]
    public function test_participants_component_renders(): void
    {
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement'],
                    1 => ['speaker' => 'negative', 'name' => 'Response'],
                ]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
        });

        Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate])
            ->assertViewIs('livewire.debates.participants')
            ->assertSet('debate.id', $this->debate->id)
            ->assertSet('currentTurnName', 'Opening Statement')
            ->assertSet('nextTurnName', 'Response')
            ->assertSet('currentSpeaker', 'affirmative')
            ->assertSet('isMyTurn', true);
    }

    /**
     * TODO-051: Debates/Participants基本機能テスト - マウント初期化
     */
    #[Test]
    public function test_mount_initializes_participants_correctly(): void
    {
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement'],
                    1 => ['speaker' => 'negative', 'name' => 'Response'],
                ]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate]);

        // 基本プロパティの確認
        $livewire->assertSet('debate.id', $this->debate->id)
            ->assertSet('currentTurnName', 'Opening Statement')
            ->assertSet('nextTurnName', 'Response')
            ->assertSet('currentSpeaker', 'affirmative')
            ->assertSet('isMyTurn', true)
            ->assertSet('isProcessing', false);

        // オンラインユーザーの初期化確認
        $onlineUsers = $livewire->get('onlineUsers');
        $this->assertArrayHasKey($this->affirmativeUser->id, $onlineUsers);
        $this->assertArrayHasKey($this->negativeUser->id, $onlineUsers);
        $this->assertFalse($onlineUsers[$this->affirmativeUser->id]); // 初期はオフライン
        $this->assertFalse($onlineUsers[$this->negativeUser->id]); // 初期はオフライン
    }

    /**
     * TODO-051: Debates/Participants基本機能テスト - AIディベートの初期化
     */
    #[Test]
    public function test_mount_with_ai_debate(): void
    {
        $aiUserId = (int)config('app.ai_user_id', 1);
        $aiUser = User::factory()->create(['id' => $aiUserId]);

        // 新しいRoomを作成してデータベース重複を回避
        $aiRoom = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'created_by' => $this->affirmativeUser->id,
        ]);

        $aiDebate = Debate::factory()->create([
            'room_id' => $aiRoom->id,
            'affirmative_user_id' => $this->affirmativeUser->id,
            'negative_user_id' => $aiUserId,
            'current_turn' => 0,
        ]);

        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement'],
                ]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $aiDebate]);

        // AIユーザーは初期からオンライン
        $onlineUsers = $livewire->get('onlineUsers');
        $this->assertFalse($onlineUsers[$this->affirmativeUser->id]); // 人間ユーザーは初期オフライン
        $this->assertTrue($onlineUsers[$aiUserId]); // AIは初期からオンライン
    }

    /**
     * TODO-051: Debates/Participantsイベント処理テスト - ターン進行イベント
     */
    #[Test]
    public function test_handle_turn_advanced_event(): void
    {
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement'],
                    1 => ['speaker' => 'negative', 'name' => 'Response'],
                    2 => ['speaker' => 'affirmative', 'name' => 'Rebuttal'],
                ]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate])
            ->assertSet('currentTurnName', 'Opening Statement')
            ->assertSet('isMyTurn', true);

        // ディベートのターンを進める
        $this->debate->update(['current_turn' => 1]);

        // ターン進行イベントをシミュレート
        $livewire->dispatch('echo-presence:debate.' . $this->debate->id . ',TurnAdvanced');

        $livewire->assertSet('currentTurnName', 'Response')
            ->assertSet('nextTurnName', 'Rebuttal')
            ->assertSet('currentSpeaker', 'negative')
            ->assertSet('isMyTurn', false) // 否定側のターンなので肯定側ユーザーは自分のターンではない
            ->assertSet('isProcessing', false);
    }

    /**
     * TODO-051: Debates/Participantsイベント処理テスト - メンバーオンラインイベント
     */
    #[Test]
    public function test_handle_member_online_event(): void
    {
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')->andReturn([]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate]);

        // メンバーオンラインイベント
        $livewire->dispatch('member-online', ['id' => $this->negativeUser->id]);

        $onlineUsers = $livewire->get('onlineUsers');
        $this->assertTrue($onlineUsers[$this->negativeUser->id]);
    }

    /**
     * TODO-051: Debates/Participantsイベント処理テスト - メンバーオフラインイベント
     */
    #[Test]
    public function test_handle_member_offline_event(): void
    {
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')->andReturn([]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate]);

        // 先にオンラインにする
        $livewire->dispatch('member-online', ['id' => $this->negativeUser->id]);
        $onlineUsers = $livewire->get('onlineUsers');
        $this->assertTrue($onlineUsers[$this->negativeUser->id]);

        // オフラインイベント
        $livewire->dispatch('member-offline', ['id' => $this->negativeUser->id]);

        $onlineUsers = $livewire->get('onlineUsers');
        $this->assertFalse($onlineUsers[$this->negativeUser->id]);
    }

    /**
     * TODO-051: Debates/Participantsイベント処理テスト - AIユーザーイベント無視
     */
    #[Test]
    public function test_ignores_ai_user_events(): void
    {
        $aiUserId = (int)config('app.ai_user_id', 1);

        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')->andReturn([]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate]);

        $initialOnlineUsers = $livewire->get('onlineUsers');

        // AIユーザーのオンライン/オフラインイベントは無視される
        $livewire->dispatch('member-online', ['id' => $aiUserId])
            ->dispatch('member-offline', ['id' => $aiUserId]);

        $finalOnlineUsers = $livewire->get('onlineUsers');
        $this->assertEquals($initialOnlineUsers, $finalOnlineUsers);
    }

    /**
     * TODO-051: Debates/Participants機能テスト - ユーザーオンライン状態確認
     */
    #[Test]
    public function test_is_user_online_method(): void
    {
        $aiUserId = (int)config('app.ai_user_id', 1);

        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')->andReturn([]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate]);

        $component = $livewire->instance();

        // AIユーザーは常にオンライン
        $this->assertTrue($component->isUserOnline($aiUserId));

        // 通常ユーザーは初期オフライン
        $this->assertFalse($component->isUserOnline($this->negativeUser->id));

        // オンラインにする
        $livewire->dispatch('member-online', ['id' => $this->negativeUser->id]);
        // コンポーネントインスタンスを再取得
        $component = $livewire->instance();
        $this->assertTrue($component->isUserOnline($this->negativeUser->id));

        // 存在しないユーザーはオフライン
        $this->assertFalse($component->isUserOnline(99999));
    }

    /**
     * TODO-051: Debates/Participants機能テスト - 手動ターン進行
     */
    #[Test]
    public function test_advance_turn_manually(): void
    {
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement'],
                ]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
            $mock->shouldReceive('advanceToNextTurn')
                ->once()
                ->withAnyArgs();
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate])
            ->assertSet('isProcessing', false);

        $livewire->call('advanceTurnManually');

        $livewire->assertSet('isProcessing', true)
            ->assertDispatched('showFlashMessage');
    }

    /**
     * TODO-051: Debates/Participants機能テスト - 処理中の重複防止
     */
    #[Test]
    public function test_advance_turn_manually_prevents_duplicate_processing(): void
    {
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')->andReturn([]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
            $mock->shouldReceive('advanceToNextTurn')->never(); // 呼ばれないことを確認
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate])
            ->set('isProcessing', true); // 処理中状態に設定

        $livewire->call('advanceTurnManually');

        // 処理中なので何も実行されない
        $livewire->assertNotDispatched('showFlashMessage');
    }

    /**
     * TODO-051: Debates/Participants機能テスト - ターン判定ロジック
     */
    #[Test]
    public function test_check_if_users_turn_logic(): void
    {
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement'],
                    1 => ['speaker' => 'negative', 'name' => 'Response'],
                ]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
        });

        // 肯定側ターンで肯定側ユーザー
        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate]);
        $livewire->assertSet('isMyTurn', true);

        // 肯定側ターンで否定側ユーザー
        $livewire = Livewire::actingAs($this->negativeUser)
            ->test(Participants::class, ['debate' => $this->debate]);
        $livewire->assertSet('isMyTurn', false);

        // ディベートのターンを否定側に変更
        $this->debate->update(['current_turn' => 1]);

        // 否定側ターンで否定側ユーザー
        $livewire = Livewire::actingAs($this->negativeUser)
            ->test(Participants::class, ['debate' => $this->debate]);
        $livewire->assertSet('isMyTurn', true);

        // 否定側ターンで肯定側ユーザー
        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate]);
        $livewire->assertSet('isMyTurn', false);
    }

    /**
     * TODO-051: Debates/Participants機能テスト - 終了状態の処理
     */
    #[Test]
    public function test_handles_finished_debate_state(): void
    {
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement'],
                ]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
        });

        // 最終ターンを超えた状態
        $this->debate->update(['current_turn' => 10]);

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate]);

        $livewire->assertSet('currentTurnName', '終了')
            ->assertSet('nextTurnName', '終了')
            ->assertSet('currentSpeaker', null)
            ->assertSet('isMyTurn', false);
    }

    /**
     * TODO-051: Debates/Participants統合テスト - 複数イベントシーケンス
     */
    #[Test]
    public function test_multiple_event_sequence(): void
    {
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement'],
                    1 => ['speaker' => 'negative', 'name' => 'Response'],
                ]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate]);

        // 1. メンバーオンライン
        $livewire->dispatch('member-online', ['id' => $this->negativeUser->id]);
        $onlineUsers = $livewire->get('onlineUsers');
        $this->assertTrue($onlineUsers[$this->negativeUser->id]);

        // 2. ターン進行
        $this->debate->update(['current_turn' => 1]);
        $livewire->dispatch('echo-presence:debate.' . $this->debate->id . ',TurnAdvanced');
        $livewire->assertSet('currentSpeaker', 'negative')
            ->assertSet('isMyTurn', false);

        // 3. メンバーオフライン
        $livewire->dispatch('member-offline', ['id' => $this->negativeUser->id]);
        $onlineUsers = $livewire->get('onlineUsers');
        $this->assertFalse($onlineUsers[$this->negativeUser->id]);
    }

    /**
     * TODO-051: Debates/Participants統合テスト - ビューレンダリング
     */
    #[Test]
    public function test_view_rendering(): void
    {
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement'],
                ]);
            $mock->shouldReceive('isFreeFormat')->andReturn(false);
            $mock->shouldReceive('getEarlyTerminationStatus')->andReturn(['status' => 'none']);
        });

        Livewire::actingAs($this->affirmativeUser)
            ->test(Participants::class, ['debate' => $this->debate])
            ->assertViewIs('livewire.debates.participants')
            ->assertSee('Opening Statement');
    }
}
