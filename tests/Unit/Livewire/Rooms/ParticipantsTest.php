<?php

namespace Tests\Unit\Livewire\Rooms;

use Tests\Unit\Livewire\BaseLivewireTest;
use App\Livewire\Rooms\Participants;
use App\Models\User;
use App\Models\Room;
use Tests\Helpers\MockHelpers;
use Livewire\Livewire;

/**
 * Rooms/Participantsコンポーネントのテスト
 *
 * 参加者表示、サイド別表示、リアルタイム更新をテスト
 */
class ParticipantsTest extends BaseLivewireTest
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
    public function test_participants_component_renders(): void
    {
        $creator = User::factory()->create(['name' => 'Creator']);
        $participant = User::factory()->create(['name' => 'Participant']);

        $room = Room::factory()->create(['created_by' => $creator->id]);

        // 参加者を追加
        $room->users()->attach($creator->id, ['side' => 'affirmative']);
        $room->users()->attach($participant->id, ['side' => 'negative']);

        Livewire::actingAs($creator)
            ->test(Participants::class, ['room' => $room])
            ->assertStatus(200)
            ->assertSet('room.id', $room->id)
            ->assertSet('affirmativeDebater', 'Creator')
            ->assertSet('negativeDebater', 'Participant');
    }

    /**
     * mount処理のテスト
     */
    public function test_mount_initializes_participants_correctly(): void
    {
        $affirmativeUser = User::factory()->create(['name' => 'Affirmative User']);
        $negativeUser = User::factory()->create(['name' => 'Negative User']);

        $room = Room::factory()->create();

        // 参加者を追加
        $room->users()->attach($affirmativeUser->id, ['side' => 'affirmative']);
        $room->users()->attach($negativeUser->id, ['side' => 'negative']);

        Livewire::test(Participants::class, ['room' => $room])
            ->assertSet('room.id', $room->id)
            ->assertSet('affirmativeDebater', 'Affirmative User')
            ->assertSet('negativeDebater', 'Negative User');
    }

    /**
     * 参加者なしの場合のテスト
     */
    public function test_mount_with_no_participants(): void
    {
        $room = Room::factory()->create();

        Livewire::test(Participants::class, ['room' => $room])
            ->assertSet('room.id', $room->id)
            ->assertSet('affirmativeDebater', null)
            ->assertSet('negativeDebater', null);
    }

    /**
     * 肯定側のみ参加の場合のテスト
     */
    public function test_mount_with_affirmative_only(): void
    {
        $affirmativeUser = User::factory()->create(['name' => 'Affirmative Only']);
        $room = Room::factory()->create();

        // 肯定側のみ参加
        $room->users()->attach($affirmativeUser->id, ['side' => 'affirmative']);

        Livewire::test(Participants::class, ['room' => $room])
            ->assertSet('affirmativeDebater', 'Affirmative Only')
            ->assertSet('negativeDebater', null);
    }

    /**
     * 否定側のみ参加の場合のテスト
     */
    public function test_mount_with_negative_only(): void
    {
        $negativeUser = User::factory()->create(['name' => 'Negative Only']);
        $room = Room::factory()->create();

        // 否定側のみ参加
        $room->users()->attach($negativeUser->id, ['side' => 'negative']);

        Livewire::test(Participants::class, ['room' => $room])
            ->assertSet('affirmativeDebater', null)
            ->assertSet('negativeDebater', 'Negative Only');
    }

    /**
     * UserJoinedRoom イベントハンドリングのテスト
     */
    public function test_user_joined_room_event_handling(): void
    {
        $room = Room::factory()->create();

        $livewire = Livewire::test(Participants::class, ['room' => $room])
            ->assertSet('affirmativeDebater', null)
            ->assertSet('negativeDebater', null);

        // 新規ユーザーが参加
        $newUser = User::factory()->create(['name' => 'New Joiner']);
        $room->users()->attach($newUser->id, ['side' => 'affirmative']);

        // UserJoinedRoomイベントをシミュレーション
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            []
        );

        // 参加者情報が更新されることを確認
        $livewire->assertSet('affirmativeDebater', 'New Joiner')
            ->assertSet('negativeDebater', null);
    }

    /**
     * UserLeftRoom イベントハンドリングのテスト
     */
    public function test_user_left_room_event_handling(): void
    {
        $affirmativeUser = User::factory()->create(['name' => 'Leaving User']);
        $negativeUser = User::factory()->create(['name' => 'Staying User']);

        $room = Room::factory()->create();

        // 最初に両方のユーザーを参加させる
        $room->users()->attach($affirmativeUser->id, ['side' => 'affirmative']);
        $room->users()->attach($negativeUser->id, ['side' => 'negative']);

        $livewire = Livewire::test(Participants::class, ['room' => $room])
            ->assertSet('affirmativeDebater', 'Leaving User')
            ->assertSet('negativeDebater', 'Staying User');

        // 肯定側ユーザーが退出
        $room->users()->detach($affirmativeUser->id);

        // UserLeftRoomイベントをシミュレーション
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserLeftRoom',
            []
        );

        // 参加者情報が更新されることを確認
        $livewire->assertSet('affirmativeDebater', null)
            ->assertSet('negativeDebater', 'Staying User');
    }

    /**
     * 複数回の参加者変更のテスト
     */
    public function test_multiple_participant_changes(): void
    {
        $room = Room::factory()->create();

        $livewire = Livewire::test(Participants::class, ['room' => $room])
            ->assertSet('affirmativeDebater', null)
            ->assertSet('negativeDebater', null);

        // 最初のユーザーが参加
        $user1 = User::factory()->create(['name' => 'User 1']);
        $room->users()->attach($user1->id, ['side' => 'affirmative']);

        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            []
        );

        $livewire->assertSet('affirmativeDebater', 'User 1');

        // 2番目のユーザーが参加
        $user2 = User::factory()->create(['name' => 'User 2']);
        $room->users()->attach($user2->id, ['side' => 'negative']);

        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            []
        );

        $livewire->assertSet('affirmativeDebater', 'User 1')
            ->assertSet('negativeDebater', 'User 2');

        // 最初のユーザーが退出
        $room->users()->detach($user1->id);

        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserLeftRoom',
            []
        );

        $livewire->assertSet('affirmativeDebater', null)
            ->assertSet('negativeDebater', 'User 2');
    }

    /**
     * サイドの再配置テスト
     */
    public function test_side_reassignment(): void
    {
        $user1 = User::factory()->create(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);

        $room = Room::factory()->create();

        // 最初は逆のサイドに配置
        $room->users()->attach($user1->id, ['side' => 'negative']);
        $room->users()->attach($user2->id, ['side' => 'affirmative']);

        $livewire = Livewire::test(Participants::class, ['room' => $room])
            ->assertSet('affirmativeDebater', 'User 2')
            ->assertSet('negativeDebater', 'User 1');

        // サイドを変更
        $room->users()->detach([$user1->id, $user2->id]);
        $room->users()->attach($user1->id, ['side' => 'affirmative']);
        $room->users()->attach($user2->id, ['side' => 'negative']);

        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            []
        );

        $livewire->assertSet('affirmativeDebater', 'User 1')
            ->assertSet('negativeDebater', 'User 2');
    }

    /**
     * 長い名前の参加者のテスト
     */
    public function test_long_participant_names(): void
    {
        $longNameUser = User::factory()->create([
            'name' => 'Very Long Participant Name That Might Cause Display Issues'
        ]);

        $room = Room::factory()->create();
        $room->users()->attach($longNameUser->id, ['side' => 'affirmative']);

        Livewire::test(Participants::class, ['room' => $room])
            ->assertSet('affirmativeDebater', 'Very Long Participant Name That Might Cause Display Issues')
            ->assertSet('negativeDebater', null);
    }

    /**
     * 特殊文字を含む名前の参加者のテスト
     */
    public function test_special_character_participant_names(): void
    {
        $specialCharUser = User::factory()->create([
            'name' => 'User <script>alert("test")</script> & 特殊文字'
        ]);

        $room = Room::factory()->create();
        $room->users()->attach($specialCharUser->id, ['side' => 'negative']);

        Livewire::test(Participants::class, ['room' => $room])
            ->assertSet('affirmativeDebater', null)
            ->assertSet('negativeDebater', 'User <script>alert("test")</script> & 特殊文字');
    }

    /**
     * ルーム再読み込みの動作テスト
     */
    public function test_room_reload_functionality(): void
    {
        $user1 = User::factory()->create(['name' => 'Initial User']);
        $room = Room::factory()->create();

        $room->users()->attach($user1->id, ['side' => 'affirmative']);

        $livewire = Livewire::test(Participants::class, ['room' => $room])
            ->assertSet('affirmativeDebater', 'Initial User');

        // データベースを直接変更（外部からの変更をシミュレート）
        $user2 = User::factory()->create(['name' => 'New User']);
        $room->users()->detach($user1->id);
        $room->users()->attach($user2->id, ['side' => 'affirmative']);

        // updateParticipantsメソッドの内部でroom.loadが呼ばれることで更新される
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            []
        );

        $livewire->assertSet('affirmativeDebater', 'New User');
    }

    /**
     * 同時イベント処理のテスト
     */
    public function test_concurrent_event_handling(): void
    {
        $room = Room::factory()->create();

        $livewire = Livewire::test(Participants::class, ['room' => $room]);

        // 複数のユーザーが同時に参加
        $users = User::factory()->count(2)->create();
        $room->users()->attach($users[0]->id, ['side' => 'affirmative']);
        $room->users()->attach($users[1]->id, ['side' => 'negative']);

        // 複数のイベントを連続で処理
        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            []
        );

        $this->simulateRealtimeEvent(
            $livewire,
            "rooms.{$room->id}",
            'UserJoinedRoom',
            []
        );

        $livewire->assertSet('affirmativeDebater', $users[0]->name)
            ->assertSet('negativeDebater', $users[1]->name);
    }

    /**
     * View レンダリングのテスト
     */
    public function test_view_rendering(): void
    {
        $affirmativeUser = User::factory()->create(['name' => 'Affirmative Debater']);
        $negativeUser = User::factory()->create(['name' => 'Negative Debater']);

        $room = Room::factory()->create();
        $room->users()->attach($affirmativeUser->id, ['side' => 'affirmative']);
        $room->users()->attach($negativeUser->id, ['side' => 'negative']);

        Livewire::test(Participants::class, ['room' => $room])
            ->assertSee('Affirmative Debater')
            ->assertSee('Negative Debater');
    }
}
