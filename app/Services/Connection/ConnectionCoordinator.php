<?php

namespace App\Services\Connection;

use App\Models\ConnectionLog;
use App\Services\Connection\Traits\ConnectionErrorHandler;
use Illuminate\Support\Facades\Log;

/**
 * 接続管理システムの統合調整役
 * 各専門ハンドラーとの連携を管理
 */
class ConnectionCoordinator
{
    use ConnectionErrorHandler;

    public function __construct(
        private ConnectionStateManager $stateManager,
        private ConnectionLogger $logger,
        private DisconnectionHandler $disconnectionHandler,
        private ReconnectionHandler $reconnectionHandler,
        private ConnectionAnalyzer $analyzer
    ) {
        //
    }

    /**
     * 新規セッション開始時に初回接続を記録
     *
     * @param int $userId
     * @param array $context
     * @return ConnectionLog|null
     */
    public function recordInitialConnection($userId, $context)
    {
        try {
            return $this->logger->recordInitialConnection($userId, $context);
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'initial_connection',
                'userId' => $userId,
                'context' => $context
            ]);
            throw $e;
        }
    }

    /**
     * ユーザーの切断を処理
     *
     * @param int $userId
     * @param array $context
     * @return mixed
     * @throws \Exception
     */
    public function handleDisconnection($userId, $context)
    {
        try {
            return $this->disconnectionHandler->handle($userId, $context);
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'disconnection',
                'userId' => $userId,
                'context' => $context
            ]);
            throw $e;
        }
    }

    /**
     * ユーザーの再接続を処理
     *
     * @param int $userId
     * @param array $context
     * @return bool
     * @throws \Exception
     */
    public function handleReconnection($userId, $context): bool
    {
        try {
            return $this->reconnectionHandler->handle($userId, $context);
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'reconnection',
                'userId' => $userId,
                'context' => $context
            ]);
            throw $e;
        }
    }

    /**
     * 永続的な切断処理
     *
     * @param int $userId
     * @param array $context
     * @return void
     */
    public function finalizeDisconnection($userId, $context)
    {
        try {
            $this->disconnectionHandler->finalizeDisconnection($userId, $context);
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'finalize_disconnection',
                'userId' => $userId,
                'context' => $context
            ]);
        }
    }

    /**
     * ハートビートに基づいてユーザーの最終アクティブ時間を更新
     *
     * @param int $userId
     * @param array $context
     * @return void
     */
    public function updateLastSeen($userId, $context)
    {
        try {
            // 現在の接続状態を確認
            $log = ConnectionLog::getLatestLog($userId, $context['type'], $context['id']);

            if ($log && $log->isConnected()) {
                // ハートビート更新
                $this->logger->updateHeartbeat($userId, $context);
            } elseif ($log && $log->isTemporarilyDisconnected()) {
                // 切断状態だがハートビートがある場合は再接続とみなす
                $this->handleReconnection($userId, $context);
            } else {
                // 接続ログがない場合は新規接続として記録
                $this->recordInitialConnection($userId, $context);
            }
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'update_last_seen',
                'userId' => $userId,
                'context' => $context
            ]);
            throw $e;
        }
    }

    // 接続状態の取得
    public function getConnectionState($userId, $context): ?string
    {
        try {
            $log = ConnectionLog::getLatestLog($userId, $context['type'], $context['id']);
            return $log ? $log->status : null;
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'get_connection_state',
                'userId' => $userId,
                'context' => $context
            ]);
            return null;
        }
    }

    // 接続品質分析
    public function getConnectionQuality($userId, int $hours = 24): array
    {
        try {
            return $this->analyzer->getBasicConnectionStats($userId, $hours);
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'get_connection_quality',
                'userId' => $userId,
                'hours' => $hours
            ]);
            return [];
        }
    }

    /**
     * 異常パターンを分析
     *
     * @param int $userId
     * @param array $context
     * @return array
     */
    public function analyzeConnectionPatterns($userId, array $context): array
    {
        try {
            return $this->analyzer->analyzeConnectionPatterns($userId, $context);
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'analyze_anomalous_patterns',
                'userId' => $userId,
                'context' => $context
            ]);
            return [];
        }
    }
}
