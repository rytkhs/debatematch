<?php

namespace App\Services;

use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use App\Events\DebateTerminated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DebateConnectionService
{
    protected $connectionManager;
    protected $debateService;

    public function __construct(ConnectionManager $connectionManager, DebateService $debateService)
    {
        $this->connectionManager = $connectionManager;
        $this->debateService = $debateService;
    }

    /**
     * サービスを初期化
     */
    public function initialize($debateId)
    {
        //
    }

    /**
     * ユーザー切断処理
     */
    public function handleUserDisconnection($userId, $debateId)
    {
        try {
            return $this->connectionManager->handleDisconnection($userId, [
                'type' => 'debate',
                'id' => $debateId
            ]);
        } catch (\Exception $e) {
            Log::error('ディベート切断処理中にエラー発生', [
                'userId' => $userId,
                'debateId' => $debateId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * ユーザー再接続処理
     */
    public function handleUserReconnection($userId, $debateId)
    {
        try {
            return $this->connectionManager->handleReconnection($userId, [
                'type' => 'debate',
                'id' => $debateId
            ]);
        } catch (\Exception $e) {
            Log::error('ディベート再接続処理中にエラー発生', [
                'userId' => $userId,
                'debateId' => $debateId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * ディベート強制終了処理
     * 評価は行わず、welcomeページにリダイレクト
     */
    public function terminateDebate($debateId, $reason = 'connection_lost')
    {
        try {
            $debate = Debate::with('room')->find($debateId);
            if (!$debate) {
                Log::warning('終了対象のディベートが見つかりません', ['debateId' => $debateId]);
                return null;
            }

            $this->debateService->terminateDebate($debate);

            Log::info('ディベートが強制終了されました', [
                'debateId' => $debate->id,
                'reason' => $reason
            ]);

            return $debate;
        } catch (\Exception $e) {
            Log::error('ディベート強制終了処理中にエラー発生', [
                'debateId' => $debateId,
                'reason' => $reason,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
