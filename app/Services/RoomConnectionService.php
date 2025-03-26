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
                if ($room->status == Room::STATUS_READY) {
                    $room->updateStatus(Room::STATUS_WAITING);
                }

                // ルーム作成者が退出した場合、ルームを削除
                if ($user->id === $room->created_by) {
                    // 他の参加者がいるかどうか確認
                    broadcast(new CreatorLeftRoom($room, $user))->toOthers();
                    $room->updateStatus(Room::STATUS_TERMINATED);
                }

                // 退出イベントをブロードキャスト
                broadcast(new UserLeftRoom($room, $user))->toOthers();

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
