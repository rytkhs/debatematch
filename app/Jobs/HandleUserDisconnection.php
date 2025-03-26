<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ConnectionManager;
use App\Models\ConnectionLog;
use App\Services\RoomConnectionService;
use App\Services\DebateConnectionService;
use Illuminate\Support\Facades\Log;

class HandleUserDisconnection implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels, InteractsWithQueue;

    protected $userId;
    protected $context;
    public $tries = 3; // 再試行回数
    public $backoff = 5; // 再試行間隔（秒）

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
     * @param ConnectionManager $connectionManager
     * @param RoomConnectionService $roomService
     * @param DebateConnectionService $debateService
     * @return void
     */
    public function handle(
        ConnectionManager $connectionManager,
        RoomConnectionService $roomService,
        DebateConnectionService $debateService
    ): void {
        try {
            Log::info('切断タイムアウト処理を開始', [
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
            if ($log && $log->status === ConnectionManager::STATUS_CONNECTED) {
                Log::info('ユーザーは既に再接続済みのため、タイムアウト処理をスキップします', [
                    'userId' => $this->userId,
                    'context' => $this->context
                ]);
                return;
            }

            // 切断を確定する
            $connectionManager->finalizeDisconnection($this->userId, $this->context);

            // コンテキストタイプに応じた処理
            if ($this->context['type'] === 'room') {
                $roomService->handleUserDisconnectionTimeout($this->userId, $this->context['id']);
            } elseif ($this->context['type'] === 'debate') {
                $debateService->terminateDebate($this->context['id'], 'user_disconnection');
            }
        } catch (\Exception $e) {
            Log::error('切断タイムアウト処理中にエラーが発生しました', [
                'userId' => $this->userId,
                'context' => $this->context,
                'error' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString()
            ]);

            throw $e; // ジョブの再試行のために例外を再スロー
        }
    }
}
