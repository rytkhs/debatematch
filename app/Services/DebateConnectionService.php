<?php

namespace App\Services;

use App\Models\Debate;
use App\Models\Room;
use App\Models\User;
use App\Events\DebateTerminated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Connection\ConnectionCoordinator;
use App\Services\Connection\Traits\ConnectionErrorHandler;

class DebateConnectionService
{
    use ConnectionErrorHandler;

    protected ConnectionCoordinator $connectionCoordinator;
    protected DebateService $debateService;

    public function __construct(ConnectionCoordinator $connectionCoordinator, DebateService $debateService)
    {
        $this->connectionCoordinator = $connectionCoordinator;
        $this->debateService = $debateService;
    }

    /**
     * サービスを初期化
     *
     * @param int $debateId
     * @return void
     */
    public function initialize(int $debateId): void
    {
        //
    }

    /**
     * ユーザー切断処理
     *
     * @param int $userId
     * @param int $debateId
     * @return mixed
     * @throws \Exception
     */
    public function handleUserDisconnection(int $userId, int $debateId)
    {
        try {
            return $this->connectionCoordinator->handleDisconnection($userId, [
                'type' => 'debate',
                'id' => $debateId
            ]);
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'debate_disconnection',
                'userId' => $userId,
                'context' => ['debateId' => $debateId]
            ]);
            throw $e;
        }
    }

    /**
     * ユーザー再接続処理
     *
     * @param int $userId
     * @param int $debateId
     * @return bool
     * @throws \Exception
     */
    public function handleUserReconnection(int $userId, int $debateId): bool
    {
        try {
            return $this->connectionCoordinator->handleReconnection($userId, [
                'type' => 'debate',
                'id' => $debateId
            ]);
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'debate_reconnection',
                'userId' => $userId,
                'context' => ['debateId' => $debateId]
            ]);
            throw $e;
        }
    }

    /**
     * ディベート強制終了処理
     * 評価は行わず、welcomeページにリダイレクト
     *
     * @param int $debateId
     * @param string $reason
     * @return Debate|null
     */
    public function terminateDebate(int $debateId, string $reason = 'connection_lost'): ?Debate
    {
        try {
            $debate = Debate::with('room')->find($debateId);
            if (!$debate) {
                $this->logWithConfig('warning', '終了対象のディベートが見つかりません', [
                    'debateId' => $debateId,
                    'reason' => $reason
                ]);
                return null;
            }

            $this->debateService->terminateDebate($debate);

            $this->logWithConfig('info', 'ディベートが強制終了されました', [
                'debateId' => $debate->id,
                'reason' => $reason
            ]);

            return $debate;
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'debate_termination',
                'userId' => null,
                'context' => [
                    'debateId' => $debateId,
                    'reason' => $reason
                ]
            ]);
            return null;
        }
    }
}
