<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Connection\ConnectionCoordinator;
use App\Enums\ConnectionStatus;
use App\Models\ConnectionLog;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use App\Events\UserLeftRoom;
use App\Events\CreatorLeftRoom;
use App\Services\DebateService;
use App\Services\Connection\Traits\ConnectionErrorHandler;

class HandleUserDisconnection implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels, InteractsWithQueue, ConnectionErrorHandler;

    protected $userId;
    protected $context;
    public $tries = 3;
    public $backoff = 5;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $context
     */
    public function __construct($userId, $context)
    {
        $this->userId = $userId;
        $this->context = $context;
    }

    /**
     * Execute the job.
     *
     * @param ConnectionCoordinator $connectionCoordinator
     * @param DebateService $debateService
     * @return void
     */
    public function handle(
        ConnectionCoordinator $connectionCoordinator,
        DebateService $debateService
    ): void {
        try {
            // ユーザーの存在確認（ソフトデリートされたユーザーも含む）
            $user = User::withTrashed()->find($this->userId);
            if (!$user) {
                $this->logWithConfig('warning', '存在しないユーザーIDによる切断タイムアウト処理をスキップしました', [
                    'userId' => $this->userId,
                    'context' => $this->context
                ]);
                return;
            }

            $this->logWithConfig('info', '切断タイムアウト処理を開始', [
                'userId' => $this->userId,
                'context' => $this->context
            ]);

            // 最新の接続状態をチェック
            $log = ConnectionLog::getLatestLog(
                $this->userId,
                $this->context['type'],
                $this->context['id']
            );

            // すでに再接続済みの場合は何もしない
            if ($log && $log->status === ConnectionStatus::CONNECTED) {
                $this->logWithConfig('info', 'ユーザーは既に再接続済みのため、タイムアウト処理をスキップします', [
                    'userId' => $this->userId,
                    'context' => $this->context
                ]);
                return;
            }

            // 切断を確定する
            $connectionCoordinator->finalizeDisconnection($this->userId, $this->context);

            // コンテキストタイプに応じた処理
            match ($this->context['type']) {
                'room' => $this->handleRoomDisconnection(),
                'debate' => $this->handleDebateDisconnection($debateService),
                default => null
            };
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'user_disconnection_timeout',
                'userId' => $this->userId,
                'context' => $this->context
            ]);

            throw $e;
        }
    }

    /**
     * ルーム切断タイムアウト後の処理
     *
     * @return void
     */
    private function handleRoomDisconnection(): void
    {
        try {
            $room = Room::find($this->context['id']);
            $user = User::withTrashed()->find($this->userId);

            if (!$room || !$user) {
                $this->logWithConfig('warning', 'タイムアウト処理のためのルームまたはユーザーが見つかりません', [
                    'userId' => $this->userId,
                    'roomId' => $this->context['id']
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
                'userId' => $this->userId,
                'context' => [
                    'roomId' => $this->context['id']
                ]
            ]);
            throw $e;
        }
    }

    /**
     * ディベート切断タイムアウト後の処理
     *
     * @param DebateService $debateService
     * @return void
     */
    private function handleDebateDisconnection(DebateService $debateService): void
    {
        try {
            $debate = Debate::with('room')->find($this->context['id']);
            if (!$debate) {
                $this->logWithConfig('warning', '終了対象のディベートが見つかりません', [
                    'debateId' => $this->context['id'],
                    'reason' => 'user_disconnection'
                ]);
                return;
            }

            $debateService->terminateDebate($debate);

            $this->logWithConfig('info', 'ディベートが強制終了されました', [
                'debateId' => $debate->id,
                'reason' => 'user_disconnection'
            ]);
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'debate_disconnection_timeout',
                'userId' => null,
                'context' => [
                    'debateId' => $this->context['id']
                ]
            ]);
            throw $e;
        }
    }
}
