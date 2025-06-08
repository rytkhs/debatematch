<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ConnectionStatus;
use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConnectionStatusFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function connection_status_component_renders_successfully()
    {
        $room = Room::factory()->create();

        Livewire::test(ConnectionStatus::class, ['room' => $room])
            ->assertStatus(200)
            ->assertViewIs('livewire.connection-status');
    }

    #[Test]
    public function connection_status_initializes_with_room()
    {
        $room = Room::factory()->create();

        Livewire::test(ConnectionStatus::class, ['room' => $room])
            ->assertSet('room.id', $room->id)
            ->assertSet('debate', null)
            ->assertSet('onlineUsers', [])
            ->assertSet('isOffline', false);
    }

    #[Test]
    public function connection_status_initializes_with_debate()
    {
        $room = Room::factory()->create();
        $debate = Debate::factory()->create(['room_id' => $room->id]);

        Livewire::test(ConnectionStatus::class, ['room' => $room, 'debate' => $debate])
            ->assertSet('room.id', $room->id)
            ->assertSet('debate.id', $debate->id);
    }

    #[Test]
    public function connection_lost_can_be_handled()
    {
        $room = Room::factory()->create();

        $livewire = Livewire::test(ConnectionStatus::class, ['room' => $room]);

        $livewire->call('handleConnectionLost')
            ->assertSet('isOffline', true);
    }

    #[Test]
    public function connection_restored_can_be_handled()
    {
        $room = Room::factory()->create();

        $livewire = Livewire::test(ConnectionStatus::class, ['room' => $room])
            ->set('isOffline', true);

        $livewire->call('handleConnectionRestored')
            ->assertSet('isOffline', false);
    }

    #[Test]
    public function member_online_event_can_be_handled()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $livewire = Livewire::test(ConnectionStatus::class, ['room' => $room]);

        $eventData = [
            'id' => $user->id,
        ];

        $livewire->call('handleMemberOnline', $eventData)
            ->assertSet('onlineUsers.' . $user->id, true);
    }

    #[Test]
    public function member_offline_event_can_be_handled()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $livewire = Livewire::test(ConnectionStatus::class, ['room' => $room])
            ->set('onlineUsers', [$user->id => true]);

        $eventData = [
            'id' => $user->id,
        ];

        $livewire->call('handleMemberOffline', $eventData);

        // オンラインユーザーリストが更新されることを確認
        $livewire->assertSet('onlineUsers.' . $user->id, false);
    }

    #[Test]
    public function is_user_online_method_works()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $livewire = Livewire::test(ConnectionStatus::class, ['room' => $room])
            ->set('onlineUsers', [$user->id => true]);

        // isUserOnlineメソッドをテスト
        $this->assertTrue($livewire->instance()->isUserOnline($user->id));
        $this->assertFalse($livewire->instance()->isUserOnline(999)); // 存在しないユーザー
    }

    #[Test]
    public function handles_invalid_event_data_gracefully()
    {
        $room = Room::factory()->create();

        $livewire = Livewire::test(ConnectionStatus::class, ['room' => $room]);

        // 無効なデータでイベントを呼び出し
        $livewire->call('handleMemberOnline', [])
            ->assertSet('onlineUsers', []);

        $livewire->call('handleMemberOffline', [])
            ->assertSet('onlineUsers', []);
    }

    #[Test]
    public function multiple_users_online_status_tracking()
    {
        $room = Room::factory()->create();
        $users = User::factory()->count(3)->create();

        $livewire = Livewire::test(ConnectionStatus::class, ['room' => $room]);

        // 複数ユーザーをオンラインに
        foreach ($users as $user) {
            $eventData = [
                'id' => $user->id,
            ];
            $livewire->call('handleMemberOnline', $eventData);
        }

        // 全ユーザーがオンラインであることを確認
        foreach ($users as $user) {
            $this->assertTrue($livewire->instance()->isUserOnline($user->id));
        }

        // 一人をオフラインに
        $livewire->call('handleMemberOffline', [
            'id' => $users[0]->id,
        ]);

        // 該当ユーザーのみオフラインになることを確認
        $this->assertFalse($livewire->instance()->isUserOnline($users[0]->id));
        $this->assertTrue($livewire->instance()->isUserOnline($users[1]->id));
        $this->assertTrue($livewire->instance()->isUserOnline($users[2]->id));
    }

    #[Test]
    public function connection_status_rapid_changes()
    {
        $room = Room::factory()->create();

        $livewire = Livewire::test(ConnectionStatus::class, ['room' => $room]);

        // 接続状態の高速切り替え
        for ($i = 0; $i < 5; $i++) {
            $livewire->call('handleConnectionLost')
                ->assertSet('isOffline', true);

            $livewire->call('handleConnectionRestored')
                ->assertSet('isOffline', false);
        }
    }
}
