<?php

namespace Tests\Unit\Livewire\Rooms;

use Tests\Unit\Livewire\BaseLivewireTest;
use App\Livewire\Rooms\StartDebateButton;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use App\Events\DebateStarted;
use App\Services\DebateService;
use Tests\Helpers\MockHelpers;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Rooms/StartDebateButtonコンポーネントのテスト
 *
 * ディベート開始ボタンの機能、権限チェック、状態管理をテスト
 */
class StartDebateButtonTest extends BaseLivewireTest
{
    use RefreshDatabase;

    protected DebateService $debateService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->debateService = app(DebateService::class);

        // AI設定のMock
        MockHelpers::mockAIConfigs();
        MockHelpers::mockDebateConfigs();

        // イベントをMock
        Event::fake([DebateStarted::class]);
    }

    /**
     * 基本的なコンポーネントレンダリングテスト
     */
    public function test_start_debate_button_component_renders(): void
    {
        $creator = User::factory()->create();
        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_READY,
        ]);

        Livewire::actingAs($creator)
            ->test(StartDebateButton::class, ['room' => $room])
            ->assertStatus(200)
            ->assertSet('room.id', $room->id)
            ->assertSet('status', Room::STATUS_READY);
    }

    /**
     * mount処理のテスト
     */
    public function test_mount_initializes_properties_correctly(): void
    {
        $creator = User::factory()->create();
        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_WAITING,
        ]);

        Livewire::actingAs($creator)
            ->test(StartDebateButton::class, ['room' => $room])
            ->assertSet('room.id', $room->id)
            ->assertSet('status', Room::STATUS_WAITING);
    }

    /**
     * 正常なディベート開始処理のテスト
     */
    public function test_start_debate_creates_debate_successfully(): void
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();

        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_READY,
        ]);

        // 参加者を追加
        $room->users()->attach($creator->id, ['side' => 'affirmative']);
        $room->users()->attach($participant->id, ['side' => 'negative']);

        $this->assertEquals(0, Debate::count());

        $livewire = Livewire::actingAs($creator)
            ->test(StartDebateButton::class, ['room' => $room]);

        // 参加者をオンラインに設定
        $livewire->dispatch('member-online', ['id' => $creator->id])
            ->dispatch('member-online', ['id' => $participant->id])
            ->call('startDebate');

        // ディベートが作成されたことを確認
        $this->assertEquals(1, Debate::count());

        $debate = Debate::first();
        $this->assertEquals($room->id, $debate->room_id);
        $this->assertEquals($creator->id, $debate->affirmative_user_id);
        $this->assertEquals($participant->id, $debate->negative_user_id);

        // ルームステータスが更新されたことを確認
        $room->refresh();
        $this->assertEquals(Room::STATUS_DEBATING, $room->status);

        // イベントが発行されたことを確認
        Event::assertDispatched(DebateStarted::class, function ($event) use ($debate, $room) {
            return $event->debateId === $debate->id && $event->roomId === $room->id;
        });
    }

    /**
     * 参加者数不足の場合のテスト
     */
    public function test_start_debate_fails_with_insufficient_participants(): void
    {
        $creator = User::factory()->create();
        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_READY,
        ]);

        // 1人だけ参加
        $room->users()->attach($creator->id, ['side' => 'affirmative']);

        Livewire::actingAs($creator)
            ->test(StartDebateButton::class, ['room' => $room])
            ->call('startDebate');

        // ディベートが作成されていないことを確認
        $this->assertEquals(0, Debate::count());

        // ルームステータスが変更されていないことを確認
        $room->refresh();
        $this->assertEquals(Room::STATUS_READY, $room->status);

        // イベントが発行されていないことを確認
        Event::assertNotDispatched(DebateStarted::class);
    }

    /**
     * 権限チェック（作成者以外）のテスト
     */
    public function test_start_debate_unauthorized_user(): void
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();
        $unauthorized = User::factory()->create();

        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_READY,
        ]);

        // 参加者を追加
        $room->users()->attach($creator->id, ['side' => 'affirmative']);
        $room->users()->attach($participant->id, ['side' => 'negative']);

        // 権限のないユーザーとしてテスト
        $response = Livewire::actingAs($unauthorized)
            ->test(StartDebateButton::class, ['room' => $room])
            ->call('startDebate');

        // ディベートが作成されていないことを確認
        $this->assertEquals(0, Debate::count());

        // リダイレクトされることを確認
        $response->assertRedirect(route('rooms.show', $room));
    }

    /**
     * すでにディベートが開始済みの場合のテスト
     */
    public function test_start_debate_already_started(): void
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();

        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_DEBATING, // すでにディベート中
        ]);

        // 参加者を追加
        $room->users()->attach($creator->id, ['side' => 'affirmative']);
        $room->users()->attach($participant->id, ['side' => 'negative']);

        Livewire::actingAs($creator)
            ->test(StartDebateButton::class, ['room' => $room])
            ->call('startDebate');

        // ディベートが追加作成されていないことを確認
        $this->assertEquals(0, Debate::count());

        // イベントが発行されていないことを確認
        Event::assertNotDispatched(DebateStarted::class);
    }

    /**
     * updateStatus イベントハンドリングのテスト
     */
    public function test_update_status_event_handling(): void
    {
        $creator = User::factory()->create();
        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_WAITING,
        ]);

        $livewire = Livewire::actingAs($creator)
            ->test(StartDebateButton::class, ['room' => $room])
            ->assertSet('status', Room::STATUS_WAITING);

        // ステータス更新イベントをシミュレーション
        $eventData = [
            'room' => ['status' => Room::STATUS_READY]
        ];

        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            $eventData
        );

        $livewire->assertSet('status', Room::STATUS_READY);
    }

    /**
     * UserLeftRoom イベントハンドリングのテスト
     */
    public function test_user_left_room_event_handling(): void
    {
        $creator = User::factory()->create();
        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_READY,
        ]);

        $livewire = Livewire::actingAs($creator)
            ->test(StartDebateButton::class, ['room' => $room])
            ->assertSet('status', Room::STATUS_READY);

        // ユーザー退出イベントをシミュレーション
        $eventData = [
            'room' => ['status' => Room::STATUS_WAITING]
        ];

        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserLeftRoom',
            $eventData
        );

        $livewire->assertSet('status', Room::STATUS_WAITING);
    }

    /**
     * DebateService連携のテスト
     */
    public function test_debate_service_integration(): void
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();

        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_READY,
        ]);

        // 参加者を追加
        $room->users()->attach($creator->id, ['side' => 'affirmative']);
        $room->users()->attach($participant->id, ['side' => 'negative']);

        // DebateServiceのMock
        $mockDebateService = $this->createMock(DebateService::class);
        $mockDebateService->expects($this->once())
            ->method('startDebate')
            ->with($this->isInstanceOf(Debate::class));

        $this->app->instance(DebateService::class, $mockDebateService);

        $livewire = Livewire::actingAs($creator)
            ->test(StartDebateButton::class, ['room' => $room]);

        $livewire->dispatch('member-online', ['id' => $creator->id])
            ->dispatch('member-online', ['id' => $participant->id])
            ->call('startDebate');
    }

    /**
     * トランザクション処理のテスト
     */
    public function test_start_debate_transaction_rollback(): void
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();

        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_READY,
        ]);

        // 参加者を追加
        $room->users()->attach($creator->id, ['side' => 'affirmative']);
        $room->users()->attach($participant->id, ['side' => 'negative']);

        // DebateServiceでエラーを発生させる
        $mockDebateService = $this->createMock(DebateService::class);
        $mockDebateService->expects($this->once())
            ->method('startDebate')
            ->willThrowException(new \Exception('Service error'));

        $this->app->instance(DebateService::class, $mockDebateService);

        try {
            $livewire = Livewire::actingAs($creator)
                ->test(StartDebateButton::class, ['room' => $room]);

            $livewire->dispatch('member-online', ['id' => $creator->id])
                ->dispatch('member-online', ['id' => $participant->id])
                ->call('startDebate');
        } catch (\Exception $e) {
            // 例外は予期される
        }

        // ディベートが作成されていないことを確認（ロールバック）
        $this->assertEquals(0, Debate::count());

        // ルームステータスが変更されていないことを確認
        $room->refresh();
        $this->assertEquals(Room::STATUS_READY, $room->status);
    }

    /**
     * 複数ユーザー同時開始のテスト
     */
    public function test_concurrent_debate_start_prevention(): void
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();

        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_READY,
        ]);

        // 参加者を追加
        $room->users()->attach($creator->id, ['side' => 'affirmative']);
        $room->users()->attach($participant->id, ['side' => 'negative']);

        // 最初のリクエスト（参加者をオンライン設定）
        $livewire = Livewire::actingAs($creator)
            ->test(StartDebateButton::class, ['room' => $room]);

        $livewire->dispatch('member-online', ['id' => $creator->id])
            ->dispatch('member-online', ['id' => $participant->id])
            ->call('startDebate');

        // ルームステータスを更新
        $room->refresh();

        // 2回目のリクエスト（既にディベート中）
        Livewire::actingAs($creator)
            ->test(StartDebateButton::class, ['room' => $room])
            ->call('startDebate');

        // ディベートが1つだけ作成されていることを確認
        $this->assertEquals(1, Debate::count());
    }

    /**
     * 様々なルームステータスでのテスト
     */
    public function test_various_room_statuses(): void
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();

        $statuses = [
            Room::STATUS_WAITING,
            Room::STATUS_DEBATING,
            Room::STATUS_FINISHED,
        ];

        foreach ($statuses as $status) {
            $room = Room::factory()->create([
                'created_by' => $creator->id,
                'status' => $status,
            ]);

            // 参加者を追加
            $room->users()->attach($creator->id, ['side' => 'affirmative']);
            $room->users()->attach($participant->id, ['side' => 'negative']);

            $initialDebateCount = Debate::count();

            Livewire::actingAs($creator)
                ->test(StartDebateButton::class, ['room' => $room])
                ->call('startDebate');

            if ($status === Room::STATUS_READY) {
                // READYの場合のみディベートが作成される
                $this->assertEquals($initialDebateCount + 1, Debate::count());
            } else {
                // その他のステータスではディベートは作成されない
                $this->assertEquals($initialDebateCount, Debate::count());
            }
        }
    }

    public function test_start_debate_fails_when_participants_are_offline()
    {
        // ルーム作成者とユーザーを作成
        $creator = User::factory()->create();
        $participant = User::factory()->create();

        // ルームを作成
        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_READY
        ]);

        // 参加者を肯定側と否定側に配置
        $room->users()->attach($creator->id, ['side' => 'affirmative']);
        $room->users()->attach($participant->id, ['side' => 'negative']);

        // 作成者としてログイン
        $this->actingAs($creator);

        // 初期のディベート数を確認
        $initialDebateCount = Debate::count();

        // コンポーネントをテスト（全員オフライン状態 = デフォルト状態）
        $component = Livewire::test(StartDebateButton::class, ['room' => $room]);

        // オンライン状態を確認（初期状態は全員オフライン）
        $this->assertFalse($component->get('onlineUsers')[$creator->id] ?? true);
        $this->assertFalse($component->get('onlineUsers')[$participant->id] ?? true);

        // startDebateを呼び出し
        $component->call('startDebate');

        // ディベートが作成されていないことを確認
        $this->assertEquals($initialDebateCount, Debate::count());

        // ルームのステータスが変更されていないことを確認
        $room->refresh();
        $this->assertEquals(Room::STATUS_READY, $room->status);
    }

    public function test_start_debate_succeeds_when_all_participants_are_online()
    {
        // ルーム作成者とユーザーを作成
        $creator = User::factory()->create();
        $participant = User::factory()->create();

        // ルームを作成
        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_READY
        ]);

        // 参加者を肯定側と否定側に配置
        $room->users()->attach($creator->id, ['side' => 'affirmative']);
        $room->users()->attach($participant->id, ['side' => 'negative']);

        // 作成者としてログイン
        $this->actingAs($creator);

        // コンポーネントをテスト（全員オンライン状態）
        $component = Livewire::test(StartDebateButton::class, ['room' => $room])
            ->set('onlineUsers', [
                $creator->id => true,
                $participant->id => true
            ])
            ->call('startDebate');

        // エラーがないことを確認
        $this->assertNull(session('error'));

        // ディベートが作成されることを確認
        $this->assertDatabaseHas('debates', [
            'room_id' => $room->id,
            'affirmative_user_id' => $creator->id,
            'negative_user_id' => $participant->id
        ]);
    }

    public function test_online_status_is_updated_via_events()
    {
        $creator = User::factory()->create();
        $room = Room::factory()->create(['created_by' => $creator->id]);

        $this->actingAs($creator);

        $component = Livewire::test(StartDebateButton::class, ['room' => $room]);

        // member-onlineイベントをトリガー
        $component->dispatch('member-online', ['id' => $creator->id]);

        // オンライン状態が更新されることを確認
        $this->assertTrue($component->get('onlineUsers')[$creator->id]);

        // member-offlineイベントをトリガー
        $component->dispatch('member-offline', ['id' => $creator->id]);

        // オフライン状態が更新されることを確認
        $this->assertFalse($component->get('onlineUsers')[$creator->id]);
    }
}
