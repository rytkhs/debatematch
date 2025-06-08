<?php

namespace Tests\Unit\Livewire;

use App\Livewire\ConnectionStatus;
use App\Models\Room;
use App\Models\User;
use App\Models\Debate;
use Livewire\Livewire;
use Tests\Unit\Livewire\BaseLivewireTest;

class ConnectionStatusTest extends BaseLivewireTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupLivewireTest();
    }

    /**
     * コンポーネントの基本レンダリングテスト
     */
    public function test_connection_status_component_renders(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->assertStatus(200)
            ->assertViewIs('livewire.connection-status');
    }

    /**
     * mount処理のテスト
     */
    public function test_mount_initializes_properties_correctly(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->assertSet('room.id', $room->id)
            ->assertSet('isOffline', false)
            ->assertSet('isPeerOffline', false)
            ->assertSet('onlineUsers', []);
    }

    /**
     * ディベート付きルームでのmountテスト
     */
    public function test_mount_with_debate(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->withDebate()->create();

        Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->assertSet('room.id', $room->id)
            ->assertSet('debate.id', $room->debate->id);
    }

    /**
     * 接続切断イベントのテスト
     */
    public function test_handle_connection_lost(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->assertSet('isOffline', false)
            ->dispatch('connection-lost')
            ->assertSet('isOffline', true);
    }

    /**
     * 接続復元イベントのテスト
     */
    public function test_handle_connection_restored(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $livewire = Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->dispatch('connection-lost')
            ->assertSet('isOffline', true);

        $livewire->dispatch('connection-restored')
            ->assertSet('isOffline', false);
    }

    /**
     * メンバーオンラインイベントのテスト
     */
    public function test_handle_member_online(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $livewire = Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->assertSet('onlineUsers', []);

        $livewire->dispatch('member-online', ['id' => 123])
            ->assertSet('onlineUsers.123', true);
    }

    /**
     * メンバーオフラインイベントのテスト
     */
    public function test_handle_member_offline(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $livewire = Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->dispatch('member-online', ['id' => 123])
            ->assertSet('onlineUsers.123', true);

        $livewire->dispatch('member-offline', ['id' => 123])
            ->assertSet('onlineUsers.123', false);
    }

    /**
     * 無効なデータでのメンバーイベントテスト
     */
    public function test_handle_member_events_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $livewire = Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->assertSet('onlineUsers', []);

        // IDが無いデータ
        $livewire->dispatch('member-online', [])
            ->assertSet('onlineUsers', []);

        // 不正なデータ構造
        $livewire->dispatch('member-online', ['user' => 123])
            ->assertSet('onlineUsers', []);
    }

    /**
     * ディベート相手のオンライン状態更新テスト
     */
    public function test_peer_status_update_in_debate(): void
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();

        $room = Room::factory()->create();
        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);
        $room->setRelation('debate', $debate);

        // 肯定側ユーザーとしてテスト
        $livewire = Livewire::actingAs($affirmativeUser)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->assertSet('isPeerOffline', false);

        // 否定側ユーザーがオフラインになる
        $livewire->dispatch('member-offline', ['id' => $negativeUser->id])
            ->assertSet('isPeerOffline', true);

        // 否定側ユーザーがオンラインに戻る
        $livewire->dispatch('member-online', ['id' => $negativeUser->id])
            ->assertSet('isPeerOffline', false);
    }

    /**
     * ディベート相手でないユーザーの状態変更テスト
     */
    public function test_non_peer_status_change(): void
    {
        $affirmativeUser = User::factory()->create();
        $negativeUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $room = Room::factory()->create();
        $debate = Debate::factory()->create([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);
        $room->setRelation('debate', $debate);

        // 肯定側ユーザーとしてテスト
        $livewire = Livewire::actingAs($affirmativeUser)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->assertSet('isPeerOffline', false);

        // 関係ないユーザーがオフラインになっても影響しない
        $livewire->dispatch('member-offline', ['id' => $otherUser->id])
            ->assertSet('isPeerOffline', false);
    }

    /**
     * UserLeftRoomイベントでの状態リセットテスト
     */
    public function test_reset_state_on_user_left_room(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $livewire = Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->dispatch('connection-lost')
            ->assertSet('isOffline', true)
            ->dispatch('member-online', ['id' => 123])
            ->assertSet('onlineUsers.123', true);

        // UserLeftRoomイベントで状態リセット
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserLeftRoom',
            []
        );

        $livewire->assertSet('isOffline', false)
            ->assertSet('isPeerOffline', false)
            ->assertSet('onlineUsers', []);
    }

    /**
     * isUserOnlineメソッドのテスト
     */
    public function test_is_user_online_method(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $livewire = Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room]);

        // 初期状態では誰もオンラインでない
        $this->assertFalse($livewire->instance()->isUserOnline(123));

        // ユーザーをオンラインにする
        $livewire->dispatch('member-online', ['id' => 123]);
        $this->assertTrue($livewire->instance()->isUserOnline(123));

        // ユーザーをオフラインにする
        $livewire->dispatch('member-offline', ['id' => 123]);
        $this->assertFalse($livewire->instance()->isUserOnline(123));
    }

    /**
     * 複数ユーザーの同時オンライン状態管理テスト
     */
    public function test_multiple_users_online_status(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $livewire = Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room]);

        // 複数ユーザーをオンラインにする
        $livewire->dispatch('member-online', ['id' => 1])
            ->dispatch('member-online', ['id' => 2])
            ->dispatch('member-online', ['id' => 3])
            ->assertSet('onlineUsers.1', true)
            ->assertSet('onlineUsers.2', true)
            ->assertSet('onlineUsers.3', true);

        // 一部をオフラインにする
        $livewire->dispatch('member-offline', ['id' => 2])
            ->assertSet('onlineUsers.1', true)
            ->assertSet('onlineUsers.2', false)
            ->assertSet('onlineUsers.3', true);
    }

    /**
     * ディベートなしでのピア状態更新テスト
     */
    public function test_peer_status_without_debate(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create(); // ディベートなし

        $livewire = Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->assertSet('isPeerOffline', false);

        // ディベートがない場合、誰がオフラインになってもピア状態は変わらない
        $livewire->dispatch('member-offline', ['id' => 123])
            ->assertSet('isPeerOffline', false);
    }

    /**
     * 接続状態の連続変更テスト
     */
    public function test_connection_status_rapid_changes(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $livewire = Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->assertSet('isOffline', false);

        // 連続的な接続状態変更
        for ($i = 0; $i < 5; $i++) {
            $livewire->dispatch('connection-lost')
                ->assertSet('isOffline', true)
                ->dispatch('connection-restored')
                ->assertSet('isOffline', false);
        }
    }

    /**
     * ビューレンダリングテスト
     */
    public function test_view_rendering(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room])
            ->assertViewIs('livewire.connection-status')
            ->assertSee('wire:offline');
    }

    /**
     * パフォーマンステスト
     */
    public function test_performance_with_many_status_changes(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $livewire = Livewire::actingAs($user)
            ->test(ConnectionStatus::class, ['room' => $room]);

        $startTime = microtime(true);

        // 多数のステータス変更
        for ($i = 0; $i < 20; $i++) {
            $livewire->dispatch('member-online', ['id' => $i])
                ->dispatch('member-offline', ['id' => $i]);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 1秒以内で処理が完了することを確認
        $this->assertLessThan(1.0, $executionTime);
    }
}
