<?php

namespace App\Services\Room;

use App\Models\Room;
use App\Models\User;
use App\Events\UserJoinedRoom;
use App\Events\UserLeftRoom;
use App\Events\CreatorLeftRoom;
use App\Services\SlackNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoomParticipationService
{
    public function __construct(
        private SlackNotifier $slackNotifier
    ) {}

    /**
     * ルームに参加する
     */
    public function joinRoom(Room $room, User $user, string $side): Room
    {
        $this->validateJoinRequest($room, $user, $side);

        // データベース操作のみをトランザクション内で実行
        $result = DB::transaction(function () use ($room, $user, $side) {
            // 参加者として登録
            $room->users()->attach($user->id, [
                'side' => $side,
            ]);

            $room->updateStatus(Room::STATUS_READY);
            $room->refresh();

            return $room;
        });

        // トランザクション外でブロードキャストと通知を実行
        $this->handleJoinNotifications($result, $user);

        return $result;
    }

    /**
     * ルームから退出する
     */
    public function leaveRoom(Room $room, User $user): Room
    {
        $isCreator = ($user->id === $room->created_by);

        // データベース操作のみをトランザクション内で実行
        $result = DB::transaction(function () use ($room, $user, $isCreator) {
            // ユーザーをルームから退出させる
            $room->users()->detach($user->id);

            // 退出後のステータス更新
            if ($room->status == Room::STATUS_READY) {
                $room->updateStatus(Room::STATUS_WAITING);
            }

            // ルーム作成者が退出した場合
            if ($isCreator) {
                $room->updateStatus(Room::STATUS_DELETED);
            }

            $room->refresh();
            return $room;
        });

        // トランザクション外でブロードキャスト実行
        $this->handleLeaveNotifications($result, $user, $isCreator);

        return $result;
    }

    /**
     * ルームに参加可能かどうかをチェック
     */
    public function canJoinRoom(Room $room, User $user): array
    {
        $room->load('users');

        if ($room->users->count() >= 2) {
            return [
                'can_join' => false,
                'error_key' => 'flash.room.join.full'
            ];
        }

        if ($room->users->contains($user->id)) {
            return [
                'can_join' => false,
                'error_key' => 'flash.room.join.already_joined'
            ];
        }

        if ($room->status !== Room::STATUS_WAITING) {
            return [
                'can_join' => false,
                'error_key' => 'flash.room.join.not_available'
            ];
        }

        return ['can_join' => true];
    }

    /**
     * 参加リクエストのバリデーション
     */
    private function validateJoinRequest(Room $room, User $user, string $side): void
    {
        $validation = $this->canJoinRoom($room, $user);

        if (!$validation['can_join']) {
            throw new \Exception(__($validation['error_key']));
        }

        // 選択された側が既に取られているかチェック
        $existingSide = $room->users()->wherePivot('side', $side)->first();
        if ($existingSide) {
            throw new \Exception(__('flash.room.join.side_taken'));
        }
    }

    /**
     * 参加時の通知処理
     */
    private function handleJoinNotifications(Room $room, User $user): void
    {
        try {
            // ホストに参加者が参加したことを通知
            broadcast(new UserJoinedRoom($room, $user))->toOthers();

            // ルーム作成者の情報を取得
            $creator = $room->creator;
            $message = "ユーザーがルームに参加しました。\n"
                . "ルーム名: " . ($room->name ?? $room->topic) . "\n"
                . "参加者: {$user->name}\n"
                . "ホスト: {$creator->name}\n"
                . "マッチングが成立し、ディベートを開始できる状態になりました。";

            $slackResult = $this->slackNotifier->send($message);
            if (!$slackResult) {
                Log::warning("Slack通知の送信に失敗しました(ユーザー参加)。 Room ID: {$room->id}, User ID: {$user->id}");
            }
        } catch (\Exception $e) {
            // ブロードキャスト/通知エラーはログに記録するが、ユーザー操作は成功として扱う
            Log::error('Room join: ブロードキャストまたは通知処理でエラーが発生しました', [
                'room_id' => $room->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 退出時の通知処理
     */
    private function handleLeaveNotifications(Room $room, User $user, bool $isCreator): void
    {
        try {
            if ($isCreator) {
                // 他の参加者に作成者が退出したことを通知
                broadcast(new CreatorLeftRoom($room, $user))->toOthers();
            } else {
                // 参加者が退出した場合の通知
                broadcast(new UserLeftRoom($room, $user))->toOthers();
            }
        } catch (\Exception $e) {
            // ブロードキャストエラーはログに記録するが、ユーザー操作は成功として扱う
            Log::error('Room exit: ブロードキャスト処理でエラーが発生しました', [
                'room_id' => $room->id,
                'user_id' => $user->id,
                'is_creator' => $isCreator,
                'error' => $e->getMessage()
            ]);
        }
    }
}
