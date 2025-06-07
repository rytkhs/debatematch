<?php

namespace Tests\Unit\Livewire\Rooms;

use Tests\Unit\Livewire\BaseLivewireTest;
use App\Livewire\Rooms\Status;
use App\Models\User;
use App\Models\Room;
use Tests\Helpers\MockHelpers;
use Livewire\Livewire;

/**
 * Rooms/Statusコンポーネントのテスト
 *
 * ルームステータス表示、リアルタイム更新をテスト
 */
class StatusTest extends BaseLivewireTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // AI設定のMock
        MockHelpers::mockAIConfigs();
        MockHelpers::mockDebateConfigs();
    }

    /**
     * 基本的なコンポーネントレンダリングテスト
     */
    public function test_status_component_renders(): void
    {
        $room = Room::factory()->create(['status' => Room::STATUS_WAITING]);

        Livewire::test(Status::class, ['room' => $room])
            ->assertStatus(200)
            ->assertSet('room.id', $room->id)
            ->assertSet('room.status', Room::STATUS_WAITING);
    }

    /**
     * mount処理のテスト
     */
    public function test_mount_initializes_room_correctly(): void
    {
        $room = Room::factory()->create(['status' => Room::STATUS_READY]);

        Livewire::test(Status::class, ['room' => $room])
            ->assertSet('room.id', $room->id)
            ->assertSet('room.status', Room::STATUS_READY);
    }

    /**
     * 各ステータスの表示テスト
     */
    public function test_various_status_display(): void
    {
        $statuses = [
            Room::STATUS_WAITING,
            Room::STATUS_READY,
            Room::STATUS_DEBATING,
            Room::STATUS_FINISHED,
        ];

        foreach ($statuses as $status) {
            $room = Room::factory()->create(['status' => $status]);

            $livewire = Livewire::test(Status::class, ['room' => $room])
                ->assertSet('room.status', $status);

            // ステータスに応じた表示内容をテスト
            switch ($status) {
                case Room::STATUS_WAITING:
                    $livewire->assertSee(__('messages.waiting_status'));
                    break;
                case Room::STATUS_READY:
                    $livewire->assertSee(__('messages.ready_status'));
                    break;
                case Room::STATUS_DEBATING:
                    $livewire->assertSee(__('messages.debate_in_progress'));
                    break;
                case Room::STATUS_FINISHED:
                    $livewire->assertSee(__('messages.finished'));
                    break;
            }
        }
    }

    /**
     * UserJoinedRoom イベントでのステータス更新テスト
     */
    public function test_user_joined_room_status_update(): void
    {
        $room = Room::factory()->create(['status' => Room::STATUS_WAITING]);

        $livewire = Livewire::test(Status::class, ['room' => $room])
            ->assertSet('room.status', Room::STATUS_WAITING);

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

        $livewire->assertSet('room.status', Room::STATUS_READY);
    }

    /**
     * UserLeftRoom イベントでのステータス更新テスト
     */
    public function test_user_left_room_status_update(): void
    {
        $room = Room::factory()->create(['status' => Room::STATUS_READY]);

        $livewire = Livewire::test(Status::class, ['room' => $room])
            ->assertSet('room.status', Room::STATUS_READY);

        // ステータス更新イベントをシミュレーション
        $eventData = [
            'room' => ['status' => Room::STATUS_WAITING]
        ];

        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserLeftRoom',
            $eventData
        );

        $livewire->assertSet('room.status', Room::STATUS_WAITING);
    }

    /**
     * 複数回のステータス変更テスト
     */
    public function test_multiple_status_changes(): void
    {
        $room = Room::factory()->create(['status' => Room::STATUS_WAITING]);

        $livewire = Livewire::test(Status::class, ['room' => $room])
            ->assertSet('room.status', Room::STATUS_WAITING);

        // WAITING -> READY
        $eventData = ['room' => ['status' => Room::STATUS_READY]];
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            $eventData
        );
        $livewire->assertSet('room.status', Room::STATUS_READY);

        // READY -> DEBATING
        $eventData = ['room' => ['status' => Room::STATUS_DEBATING]];
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            $eventData
        );
        $livewire->assertSet('room.status', Room::STATUS_DEBATING);

        // DEBATING -> FINISHED
        $eventData = ['room' => ['status' => Room::STATUS_FINISHED]];
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserLeftRoom',
            $eventData
        );
        $livewire->assertSet('room.status', Room::STATUS_FINISHED);
    }

    /**
     * 無効なイベントデータのテスト
     */
    public function test_invalid_event_data_handling(): void
    {
        $room = Room::factory()->create(['status' => Room::STATUS_WAITING]);

        $livewire = Livewire::test(Status::class, ['room' => $room])
            ->assertSet('room.status', Room::STATUS_WAITING);

        // 空のイベントデータ
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            []
        );

        // ステータスが変更されていないことを確認
        $livewire->assertSet('room.status', Room::STATUS_WAITING);

        // room キーが無いイベントデータ
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            ['other' => 'data']
        );

        // ステータスが変更されていないことを確認
        $livewire->assertSet('room.status', Room::STATUS_WAITING);

        // status キーが無いイベントデータ
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            ['room' => ['other' => 'data']]
        );

        // ステータスが変更されていないことを確認
        $livewire->assertSet('room.status', Room::STATUS_WAITING);
    }

    /**
     * ステータス変更とビューの更新テスト
     */
    public function test_status_change_view_update(): void
    {
        $room = Room::factory()->create(['status' => Room::STATUS_WAITING]);

        $livewire = Livewire::test(Status::class, ['room' => $room]);

        // 最初のステータス表示を確認
        $livewire->assertSee(__('messages.waiting_status'));

        // READYに変更
        $eventData = ['room' => ['status' => Room::STATUS_READY]];
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            $eventData
        );

        // 新しいステータス表示を確認
        $livewire->assertSee(__('messages.ready_status'))
            ->assertDontSee(__('messages.waiting_status'));
    }

    /**
     * 同時多発的なステータス更新の処理テスト
     */
    public function test_concurrent_status_updates(): void
    {
        $room = Room::factory()->create(['status' => Room::STATUS_WAITING]);

        $livewire = Livewire::test(Status::class, ['room' => $room]);

        // 複数のイベントを連続で発火
        $events = [
            ['room' => ['status' => Room::STATUS_READY]],
            ['room' => ['status' => Room::STATUS_DEBATING]],
            ['room' => ['status' => Room::STATUS_FINISHED]],
        ];

        foreach ($events as $eventData) {
            $this->simulateRealtimeEvent(
                $livewire,
                "rooms.{$room->id}",
                'UserJoinedRoom',
                $eventData
            );
        }

        // 最後のステータスが適用されていることを確認
        $livewire->assertSet('room.status', Room::STATUS_FINISHED);
    }

    /**
     * 異なるイベントタイプでの処理テスト
     */
    public function test_different_event_types(): void
    {
        $room = Room::factory()->create(['status' => Room::STATUS_WAITING]);

        $livewire = Livewire::test(Status::class, ['room' => $room]);

        // UserJoinedRoomイベント
        $eventData = ['room' => ['status' => Room::STATUS_READY]];
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            $eventData
        );
        $livewire->assertSet('room.status', Room::STATUS_READY);

        // UserLeftRoomイベント
        $eventData = ['room' => ['status' => Room::STATUS_WAITING]];
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserLeftRoom',
            $eventData
        );
        $livewire->assertSet('room.status', Room::STATUS_WAITING);
    }

    /**
     * 無効なイベントデータの処理テスト（異なるルームの模擬）
     */
    public function test_different_room_id_event(): void
    {
        $room = Room::factory()->create(['status' => Room::STATUS_WAITING]);

        $livewire = Livewire::test(Status::class, ['room' => $room])
            ->assertSet('room.status', Room::STATUS_WAITING);

        // 不正なイベントデータをシミュレーション（roomキーなし）
        $eventData = ['other_room' => ['status' => Room::STATUS_READY]];
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            $eventData
        );

        // 元のルームのステータスは変更されていないことを確認
        $livewire->assertSet('room.status', Room::STATUS_WAITING);
    }

    /**
     * ステータス変更のパフォーマンステスト
     */
    public function test_status_update_performance(): void
    {
        $room = Room::factory()->create(['status' => Room::STATUS_WAITING]);

        $livewire = Livewire::test(Status::class, ['room' => $room]);

        $startTime = microtime(true);

        // 10回のステータス更新
        for ($i = 0; $i < 10; $i++) {
            $status = $i % 2 === 0 ? Room::STATUS_READY : Room::STATUS_WAITING;
            $eventData = ['room' => ['status' => $status]];

            $this->simulateRealtimeEvent(
                $livewire,
                "rooms.{$room->id}",
                'UserJoinedRoom',
                $eventData
            );
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 1秒以内で処理が完了することを確認
        $this->assertLessThan(1.0, $executionTime);

        // 最後のステータスが正しく設定されていることを確認
        $livewire->assertSet('room.status', Room::STATUS_WAITING);
    }

    /**
     * ステータス更新時のメモリ使用量テスト
     */
    public function test_status_update_memory_usage(): void
    {
        $room = Room::factory()->create(['status' => Room::STATUS_WAITING]);

        $livewire = Livewire::test(Status::class, ['room' => $room]);

        $initialMemory = memory_get_usage();

        // 複数回のステータス更新
        for ($i = 0; $i < 20; $i++) {
            $status = [
                Room::STATUS_WAITING,
                Room::STATUS_READY,
                Room::STATUS_DEBATING,
                Room::STATUS_FINISHED
            ][$i % 4];

            $eventData = ['room' => ['status' => $status]];

            $this->simulateRealtimeEvent(
                $livewire,
                "rooms.{$room->id}",
                'UserJoinedRoom',
                $eventData
            );
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        // メモリ使用量の増加が1MB以下であることを確認
        $this->assertLessThan(1024 * 1024, $memoryIncrease);
    }

    /**
     * View レンダリングのテスト
     */
    public function test_view_rendering(): void
    {
        $room = Room::factory()->create(['status' => Room::STATUS_READY]);

        Livewire::test(Status::class, ['room' => $room])
            ->assertViewIs('livewire.rooms.status')
            ->assertSee(__('messages.ready_status'));
    }

    /**
     * ステータス文字列の妥当性テスト
     */
    public function test_status_string_validity(): void
    {
        $validStatuses = [
            Room::STATUS_WAITING,
            Room::STATUS_READY,
            Room::STATUS_DEBATING,
            Room::STATUS_FINISHED,
        ];

        foreach ($validStatuses as $status) {
            $room = Room::factory()->create(['status' => $status]);

            Livewire::test(Status::class, ['room' => $room])
                ->assertSet('room.status', $status);

            // ステータスが有効な値であることを確認
            $this->assertContains($status, $validStatuses);
        }
    }
}
