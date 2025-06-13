<?php

namespace App\Services;

use App\Models\Room;
use App\Models\User;
use App\Events\UserLeftRoom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\CreatorLeftRoom;
use App\Services\Connection\ConnectionCoordinator;
use App\Services\Connection\Traits\ConnectionErrorHandler;

class RoomConnectionService
{
    use ConnectionErrorHandler;

    protected ConnectionCoordinator $connectionCoordinator;

    public function __construct(ConnectionCoordinator $connectionCoordinator)
    {
        $this->connectionCoordinator = $connectionCoordinator;
    }

    /**
     * サービスを初期化
     *
     * @param int $roomId
     * @return void
     */
    public function initialize(int $roomId): void
    {
        //
    }

    /**
     * ユーザー切断処理
     *
     * @param int $userId
     * @param int $roomId
     * @return mixed
     * @throws \Exception
     */
    public function handleUserDisconnection(int $userId, int $roomId)
    {
        try {
            return $this->connectionCoordinator->handleDisconnection($userId, [
                'type' => 'room',
                'id' => $roomId
            ]);
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'room_disconnection',
                'userId' => $userId,
                'context' => ['roomId' => $roomId]
            ]);
            throw $e;
        }
    }

    /**
     * ユーザー再接続処理
     *
     * @param int $userId
     * @param int $roomId
     * @return bool
     * @throws \Exception
     */
    public function handleUserReconnection(int $userId, int $roomId): bool
    {
        try {
            return $this->connectionCoordinator->handleReconnection($userId, [
                'type' => 'room',
                'id' => $roomId
            ]);
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'room_reconnection',
                'userId' => $userId,
                'context' => ['roomId' => $roomId]
            ]);
            throw $e;
        }
    }

    /**
     * 切断タイムアウト後の処理
     *
     * @param int $userId
     * @param int $roomId
     * @return void
     */
    public function handleUserDisconnectionTimeout(int $userId, int $roomId): void
    {
        try {
            $room = Room::find($roomId);
            $user = User::withTrashed()->find($userId);

            if (!$room || !$user) {
                $this->logWithConfig('warning', 'タイムアウト処理のためのルームまたはユーザーが見つかりません', [
                    'userId' => $userId,
                    'roomId' => $roomId
                ]);
                return;
            }

            DB::transaction(function () use ($room, $user) {
                // ユーザーをルームから削除
                $room->users()->detach($user->id);

                // ルームの状態を更新
                if ($room->status === Room::STATUS_READY) {
                    $room->updateStatus(Room::STATUS_WAITING);
                }

                // 作成者退出フラグ
                $isCreator = ($user->id === $room->created_by);

                // ルーム作成者が退出した場合
                if ($isCreator) {
                    // ルームを強制終了状態に更新
                    $room->updateStatus(Room::STATUS_TERMINATED);
                }

                // トランザクション成功後にブロードキャスト
                DB::afterCommit(function () use ($room, $user, $isCreator) {
                    if ($isCreator) {
                        // 他の参加者がいる場合のみ、作成者が退出したことを通知
                        if ($room->users()->count() > 0) {
                            broadcast(new CreatorLeftRoom($room, $user))->toOthers();
                        }
                    } else {
                        // 参加者が退出した場合の通知
                        broadcast(new UserLeftRoom($room, $user))->toOthers();
                    }
                });

                $this->logWithConfig('info', 'ユーザーがタイムアウトによりルームから退出しました', [
                    'userId' => $user->id,
                    'roomId' => $room->id
                ]);
            });
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'room_disconnection_timeout',
                'userId' => $userId,
                'context' => ['roomId' => $roomId]
            ]);
        }
    }
}
