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

    public $tries = 3;
    public $backoff = 5;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $context
     */
    public function __construct(private $userId, private $context)
    {
        //
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

            // ユーザーがルームチャネルから切断された直後に、そのルームに関連するディベートチャネルに接続しているか確認
            // Pusherのmember_removedイベントからHandleUserDisconnectionがディスパッチされるまでに
            // タイムラグがあるため、このジョブが実行される時点でディベートチャネルに接続済みであれば、
            // それは意図的な移動と判断する。
            if ($room->debate) {
                $hasActiveDebateConnection = ConnectionLog::where('user_id', $this->userId)
                    ->where('context_type', 'debate')
                    ->where('context_id', $room->debate->id)
                    ->where('status', ConnectionStatus::CONNECTED)
                    ->exists();

                if ($hasActiveDebateConnection) {
                    $this->logWithConfig('info', 'ユーザーがそのルームのディベートチャネルに再接続済みのため、ルーム切断処理をスキップします', [
                        'userId' => $this->userId,
                        'roomId' => $this->context['id'],
                        'debateId' => $room->debate->id
                    ]);
                    return;
                }
            }

            DB::transaction(function () use ($room, $user) {
                // トランザクション内で最新の状態を再取得
                $room->refresh();

                // ユーザーをルームから削除
                $room->users()->detach($user->id);

                // 最新のルーム状態に応じた処理
                match ($room->status) {
                    Room::STATUS_WAITING => null,
                    Room::STATUS_READY => $room->updateStatus(Room::STATUS_WAITING),
                    Room::STATUS_DEBATING => $this->handleDebatingRoomDisconnection($room, $user),

                    // 終了状態グループ（通常発生しない）
                    Room::STATUS_FINISHED,
                    Room::STATUS_TERMINATED,
                    Room::STATUS_DELETED => $this->logWithConfig('debug', 'ルーム終了状態からの退出', [
                        'userId' => $user->id,
                        'roomId' => $room->id,
                        'status' => $room->status
                    ]),

                    default => $this->logWithConfig('warning', '不明なルーム状態での退出処理', [
                        'userId' => $user->id,
                        'roomId' => $room->id,
                        'roomStatus' => $room->status
                    ])
                };

                // 作成者退出フラグ
                $isCreator = ($user->id === $room->created_by);

                // ルーム作成者が退出した場合
                if ($isCreator) {
                    // 現在の状態が終了状態でない場合のみ強制終了
                    if (!in_array($room->status, [Room::STATUS_FINISHED, Room::STATUS_TERMINATED, Room::STATUS_DELETED])) {
                        $room->updateStatus(Room::STATUS_TERMINATED);
                    }
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
                    'roomId' => $room->id,
                    'finalStatus' => $room->status
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
     * ルームでの切断かつディベート中の場合の処理(参加者がルームでオフラインになった直後にディベートが開始されるエッジケース対策)
     *
     * @param Room $room
     * @param User $user
     * @return void
     */
    private function handleDebatingRoomDisconnection(Room $room, User $user): void
    {
        // ディベート中の場合、関連するディベートを強制終了
        if ($room->debate) {
            $debateService = app(DebateService::class);
            $debateService->terminateDebate($room->debate);
            $this->logWithConfig('info', 'ディベートが参加者切断により強制終了されました', [
                'userId' => $user->id,
                'roomId' => $room->id,
                'debateId' => $room->debate->id
            ]);
        } else {
            $this->logWithConfig('warning', 'ディベート中ルームだがディベートレコードが見つかりません', [
                'userId' => $user->id,
                'roomId' => $room->id
            ]);
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

            if (!$debate->room) {
                $this->logWithConfig('warning', 'ディベートに関連するルームが見つかりません', [
                    'debateId' => $debate->id,
                    'reason' => 'user_disconnection'
                ]);
                return;
            }

            DB::transaction(function () use ($debateService, $debate) {
                // トランザクション内で最新の状態を再取得
                $debate->refresh();
                $debate->room->refresh();

                // 最新のルーム状態に応じた処理
                match ($debate->room->status) {
                    Room::STATUS_DEBATING => $this->performDebateTermination($debateService, $debate),

                    // 終了状態グループ（通常は発生しない）
                    Room::STATUS_FINISHED,
                    Room::STATUS_TERMINATED,
                    Room::STATUS_DELETED => $this->logWithConfig('debug', 'ディベート終了状態での切断処理', [
                        'debateId' => $debate->id,
                        'roomStatus' => $debate->room->status
                    ]),

                    default => $this->logWithConfig('warning', '予期しないルーム状態でのディベート切断', [
                        'debateId' => $debate->id,
                        'roomStatus' => $debate->room->status,
                        'reason' => 'user_disconnection'
                    ])
                };
            });
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

    /**
     * ディベート強制終了の実行
     *
     * @param DebateService $debateService
     * @param Debate $debate
     * @return void
     */
    private function performDebateTermination(DebateService $debateService, Debate $debate): void
    {
        $debateService->terminateDebate($debate);

        $this->logWithConfig('info', 'ディベートが強制終了されました', [
            'debateId' => $debate->id,
            'reason' => 'user_disconnection'
        ]);
    }
}
