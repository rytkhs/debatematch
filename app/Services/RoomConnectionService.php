<?php

namespace App\Services;

use App\Models\Room;
use App\Models\User;
use App\Events\UserLeftRoom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\CreatorLeftRoom;
class RoomConnectionService
{
    protected $connectionManager;

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * サービスを初期化
     */
    public function initialize($roomId)
    {
        //
    }

    /**
     * ユーザー切断処理
     */
    public function handleUserDisconnection($userId, $roomId)
    {
        try {
            return $this->connectionManager->handleDisconnection($userId, [
                'type' => 'room',
                'id' => $roomId
            ]);
        } catch (\Exception $e) {
            Log::error('ルーム切断処理中にエラー発生', [
                'userId' => $userId,
                'roomId' => $roomId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * ユーザー再接続処理
     */
    public function handleUserReconnection($userId, $roomId)
    {
        try {
            return $this->connectionManager->handleReconnection($userId, [
                'type' => 'room',
                'id' => $roomId
            ]);
        } catch (\Exception $e) {
            Log::error('ルーム再接続処理中にエラー発生', [
                'userId' => $userId,
                'roomId' => $roomId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 切断タイムアウト後の処理
     */
    public function handleUserDisconnectionTimeout($userId, $roomId)
    {
        try {

            $room = Room::find($roomId);
            $user = User::find($userId);

            if (!$room || !$user) {
                Log::warning('タイムアウト処理のためのルームまたはユーザーが見つかりません', [
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

                Log::info('ユーザーがタイムアウトによりルームから退出しました', [
                    'userId' => $user->id,
                    'roomId' => $room->id
                ]);
            });
        } catch (\Exception $e) {
            Log::error('ルーム切断タイムアウト処理中にエラー発生', [
                'userId' => $userId,
                'roomId' => $roomId,
                'error' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString()
            ]);
        }
    }
}
