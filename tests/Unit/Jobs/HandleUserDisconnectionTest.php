<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\HandleUserDisconnection;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use App\Services\Connection\ConnectionCoordinator;
use App\Services\DebateService;
use App\Enums\ConnectionStatus;
use App\Models\ConnectionLog;
use App\Events\UserLeftRoom;
use App\Events\CreatorLeftRoom;
use App\Events\DebateTerminated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\MockHelpers;

class HandleUserDisconnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        MockHelpers::mockAIConfigs();
        MockHelpers::mockDebateConfigs();
        Event::fake();
    }

    public function test_handleRoomDisconnection_terminates_debate_when_room_is_debating()
    {
        // テストデータの準備
        $creator = User::factory()->create();
        $participant = User::factory()->create();

        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_DEBATING,
        ]);

        // 参加者を追加
        $room->users()->attach($creator->id, ['side' => 'affirmative']);
        $room->users()->attach($participant->id, ['side' => 'negative']);

        // ディベートを作成
        $debate = Debate::create([
            'room_id' => $room->id,
            'affirmative_user_id' => $creator->id,
            'negative_user_id' => $participant->id,
            'current_turn' => 1,
        ]);

        // 参加者の接続ログを作成
        ConnectionLog::create([
            'user_id' => $participant->id,
            'context_type' => 'room',
            'context_id' => $room->id,
            'status' => ConnectionStatus::TEMPORARILY_DISCONNECTED,
            'disconnected_at' => now(),
        ]);

        // ジョブの実行
        $job = new HandleUserDisconnection($participant->id, [
            'type' => 'room',
            'id' => $room->id
        ]);

        $connectionCoordinator = app(ConnectionCoordinator::class);
        $debateService = app(DebateService::class);

        $job->handle($connectionCoordinator, $debateService);

        // ルームが強制終了状態になっていることを確認
        $room->refresh();
        $this->assertEquals(Room::STATUS_TERMINATED, $room->status);

        // DebateTerminatedイベントが発行されることを確認
        Event::assertDispatched(DebateTerminated::class, function ($event) use ($debate) {
            return $event->debate->id === $debate->id;
        });

        // 参加者がルームから削除されていることを確認
        $this->assertFalse($room->users()->where('user_id', $participant->id)->exists());
    }

    public function test_handleRoomDisconnection_creator_leaves_debating_room()
    {
        // 作成者が切断した場合のテスト
        $creator = User::factory()->create();
        $participant = User::factory()->create();

        $room = Room::factory()->create([
            'created_by' => $creator->id,
            'status' => Room::STATUS_DEBATING,
        ]);

        $room->users()->attach($creator->id, ['side' => 'affirmative']);
        $room->users()->attach($participant->id, ['side' => 'negative']);

        $debate = Debate::create([
            'room_id' => $room->id,
            'affirmative_user_id' => $creator->id,
            'negative_user_id' => $participant->id,
            'current_turn' => 1,
        ]);

        ConnectionLog::create([
            'user_id' => $creator->id,
            'context_type' => 'room',
            'context_id' => $room->id,
            'status' => ConnectionStatus::TEMPORARILY_DISCONNECTED,
            'disconnected_at' => now(),
        ]);

        $job = new HandleUserDisconnection($creator->id, [
            'type' => 'room',
            'id' => $room->id
        ]);

        $connectionCoordinator = app(ConnectionCoordinator::class);
        $debateService = app(DebateService::class);

        $job->handle($connectionCoordinator, $debateService);

        // ルームが強制終了状態になっていることを確認
        $room->refresh();
        $this->assertEquals(Room::STATUS_TERMINATED, $room->status);

        // CreatorLeftRoomイベントが発行されることを確認
        Event::assertDispatched(CreatorLeftRoom::class);

        // DebateTerminatedイベントも発行されることを確認
        Event::assertDispatched(DebateTerminated::class);
    }

    public function test_handleRoomDisconnection_no_debate_when_room_debating()
    {
        // ディベートレコードが存在しない異常ケース
        $participant = User::factory()->create();

        $room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
        ]);

        $room->users()->attach($participant->id, ['side' => 'affirmative']);

        ConnectionLog::create([
            'user_id' => $participant->id,
            'context_type' => 'room',
            'context_id' => $room->id,
            'status' => ConnectionStatus::TEMPORARILY_DISCONNECTED,
            'disconnected_at' => now(),
        ]);

        $job = new HandleUserDisconnection($participant->id, [
            'type' => 'room',
            'id' => $room->id
        ]);

        $connectionCoordinator = app(ConnectionCoordinator::class);
        $debateService = app(DebateService::class);

        // エラーが発生しないことを確認
        $job->handle($connectionCoordinator, $debateService);

        // 参加者がルームから削除されていることを確認
        $this->assertFalse($room->users()->where('user_id', $participant->id)->exists());

        // DebateTerminatedイベントは発行されない
        Event::assertNotDispatched(DebateTerminated::class);
    }

    public function test_handleRoomDisconnection_all_statuses()
    {
        $statuses = [
            Room::STATUS_WAITING,
            Room::STATUS_READY,
            Room::STATUS_FINISHED,
            Room::STATUS_TERMINATED,
            Room::STATUS_DELETED,
        ];

        foreach ($statuses as $status) {
            $participant = User::factory()->create();

            $room = Room::factory()->create([
                'status' => $status,
            ]);

            $room->users()->attach($participant->id, ['side' => 'affirmative']);

            ConnectionLog::create([
                'user_id' => $participant->id,
                'context_type' => 'room',
                'context_id' => $room->id,
                'status' => ConnectionStatus::TEMPORARILY_DISCONNECTED,
                'disconnected_at' => now(),
            ]);

            $job = new HandleUserDisconnection($participant->id, [
                'type' => 'room',
                'id' => $room->id
            ]);

            $connectionCoordinator = app(ConnectionCoordinator::class);
            $debateService = app(DebateService::class);

            // エラーが発生しないことを確認
            $job->handle($connectionCoordinator, $debateService);

            // 参加者がルームから削除されていることを確認
            $this->assertFalse($room->users()->where('user_id', $participant->id)->exists());

            // STATUS_READYの場合のみ状態変更を確認
            if ($status === Room::STATUS_READY) {
                $room->refresh();
                $this->assertEquals(Room::STATUS_WAITING, $room->status);
            }
        }
    }

    public function test_handleDebateDisconnection_all_room_statuses()
    {
        $statuses = [
            Room::STATUS_DEBATING,
            Room::STATUS_FINISHED,
            Room::STATUS_TERMINATED,
            Room::STATUS_DELETED,
        ];

        foreach ($statuses as $status) {
            $creator = User::factory()->create();
            $participant = User::factory()->create();

            $room = Room::factory()->create([
                'created_by' => $creator->id,
                'status' => $status,
            ]);

            $room->users()->attach($creator->id, ['side' => 'affirmative']);
            $room->users()->attach($participant->id, ['side' => 'negative']);

            $debate = Debate::create([
                'room_id' => $room->id,
                'affirmative_user_id' => $creator->id,
                'negative_user_id' => $participant->id,
                'current_turn' => 1,
            ]);

            ConnectionLog::create([
                'user_id' => $participant->id,
                'context_type' => 'debate',
                'context_id' => $debate->id,
                'status' => ConnectionStatus::TEMPORARILY_DISCONNECTED,
                'disconnected_at' => now(),
            ]);

            $job = new HandleUserDisconnection($participant->id, [
                'type' => 'debate',
                'id' => $debate->id
            ]);

            $connectionCoordinator = app(ConnectionCoordinator::class);
            $debateService = app(DebateService::class);

            // エラーが発生しないことを確認
            $job->handle($connectionCoordinator, $debateService);

            // STATUS_DEBATINGの場合のみDebateTerminatedイベントが発行される
            if ($status === Room::STATUS_DEBATING) {
                Event::assertDispatched(DebateTerminated::class);
                $room->refresh();
                $this->assertEquals(Room::STATUS_TERMINATED, $room->status);
            } else {
                // その他の状態では特別な処理なし
                $this->assertEquals($status, $room->fresh()->status);
            }

            // イベントをリセット
            Event::fake();
        }
    }

    public function test_handleRoomDisconnection_race_condition_room_status_changed()
    {
        // 競合状態のテスト: ジョブ実行中にルーム状態が変更される場合
        $participant = User::factory()->create();

        $room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
        ]);

        $room->users()->attach($participant->id, ['side' => 'affirmative']);

        $debate = Debate::create([
            'room_id' => $room->id,
            'affirmative_user_id' => $participant->id,
            'negative_user_id' => User::factory()->create()->id,
            'current_turn' => 1,
        ]);

        ConnectionLog::create([
            'user_id' => $participant->id,
            'context_type' => 'room',
            'context_id' => $room->id,
            'status' => ConnectionStatus::TEMPORARILY_DISCONNECTED,
            'disconnected_at' => now(),
        ]);

        // ジョブ実行前にルーム状態を変更（他のプロセスによる変更をシミュレート）
        DB::transaction(function () use ($room, $debate) {
            $room->updateStatus(Room::STATUS_FINISHED);
            $debate->update(['status' => 'finished']);
        });

        $job = new HandleUserDisconnection($participant->id, [
            'type' => 'room',
            'id' => $room->id
        ]);

        $connectionCoordinator = app(ConnectionCoordinator::class);
        $debateService = app(DebateService::class);

        // ジョブ実行（エラーが発生しないことを確認）
        $job->handle($connectionCoordinator, $debateService);

        // ルーム状態が変更されていないことを確認（既にFINISHEDのため）
        $room->refresh();
        $this->assertEquals(Room::STATUS_FINISHED, $room->status);

        // 参加者がルームから削除されていることを確認
        $this->assertFalse($room->users()->where('user_id', $participant->id)->exists());

        // DebateTerminatedイベントは発行されない（既に終了済みのため）
        Event::assertNotDispatched(DebateTerminated::class);
    }

    public function test_handleDebateDisconnection_race_condition_debate_already_finished()
    {
        // 競合状態のテスト: ディベートが既に終了している場合
        $room = Room::factory()->create([
            'status' => Room::STATUS_FINISHED, // ルーム状態を終了に設定
        ]);

        $debate = Debate::create([
            'room_id' => $room->id,
            'affirmative_user_id' => User::factory()->create()->id,
            'negative_user_id' => User::factory()->create()->id,
            'current_turn' => 1,
        ]);

        $job = new HandleUserDisconnection(null, [
            'type' => 'debate',
            'id' => $debate->id
        ]);

        $connectionCoordinator = app(ConnectionCoordinator::class);
        $debateService = app(DebateService::class);

        // ジョブ実行（エラーが発生しないことを確認）
        $job->handle($connectionCoordinator, $debateService);

        // ルーム状態が変更されていないことを確認（既にFINISHEDのため）
        $room->refresh();
        $this->assertEquals(Room::STATUS_FINISHED, $room->status);

        // DebateTerminatedイベントは発行されない（既に終了済みのため）
        Event::assertNotDispatched(DebateTerminated::class);
    }
}
