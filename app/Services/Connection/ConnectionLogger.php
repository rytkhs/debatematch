<?php

namespace App\Services\Connection;

use App\Models\ConnectionLog;
use App\Models\User;
use App\Enums\ConnectionStatus;
use App\Services\Connection\Traits\ConnectionErrorHandler;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConnectionLogger
{
    use ConnectionErrorHandler;

    private ConnectionStateManager $stateManager;

    public function __construct(ConnectionStateManager $stateManager)
    {
        $this->stateManager = $stateManager;
    }

    /**
     * 初回接続を記録
     *
     * @param int $userId
     * @param array $context
     * @return ConnectionLog|null
     */
    public function recordInitialConnection(int $userId, array $context): ?ConnectionLog
    {
        try {
            // ユーザーの存在確認
            if (!$this->validateUser($userId)) {
                return null;
            }

            // 既存の接続をチェック
            $existingLog = $this->getLatestLog($userId, $context);
            if ($existingLog && $existingLog->isConnected()) {
                $this->logWithConfig('info', 'ユーザーは既に接続中です', [
                    'userId' => $userId,
                    'context' => $context
                ]);
                return $existingLog;
            }

            return ConnectionLog::recordInitialConnection(
                $userId,
                $context['type'],
                $context['id']
            );
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
     * 切断を記録
     *
     * @param int $userId
     * @param array $context
     * @param array $metadata
     * @return ConnectionLog|null
     */
    public function recordDisconnection(int $userId, array $context, array $metadata = []): ?ConnectionLog
    {
        try {
            // ユーザーの存在確認
            if (!$this->validateUser($userId)) {
                return null;
            }

            // 現在の接続状態を確認
            $currentLog = $this->getLatestLog($userId, $context);
            if (!$this->stateManager->canDisconnect($currentLog)) {
                $this->logWithConfig('info', '切断処理がスキップされました', [
                    'userId' => $userId,
                    'context' => $context,
                    'reason' => '切断不可能な状態',
                    'current_status' => $currentLog?->status
                ]);
                return null;
            }

            return DB::transaction(function () use ($userId, $context, $metadata) {
                // クライアント情報を取得
                $connectionMetadata = $this->buildConnectionMetadata($metadata, 'disconnection');

                $log = ConnectionLog::create([
                    'user_id' => $userId,
                    'context_type' => $context['type'],
                    'context_id' => $context['id'],
                    'status' => ConnectionStatus::TEMPORARILY_DISCONNECTED,
                    'disconnected_at' => Carbon::now(),
                    'metadata' => $connectionMetadata
                ]);

                $this->logWithConfig('info', 'ユーザー切断を記録しました', [
                    'userId' => $userId,
                    'context' => $context,
                    'log_id' => $log->id
                ]);

                return $log;
            });
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
     * 再接続を記録
     *
     * @param int $userId
     * @param array $context
     * @param array $metadata
     * @return bool
     */
    public function recordReconnection(int $userId, array $context, array $metadata = []): bool
    {
        try {
            // ユーザーの存在確認
            if (!$this->validateUser($userId)) {
                return false;
            }

            // 現在の接続状態を確認
            $currentLog = $this->getLatestLog($userId, $context);
            if (!$this->stateManager->canReconnect($currentLog)) {
                $this->logWithConfig('info', '再接続処理がスキップされました', [
                    'userId' => $userId,
                    'context' => $context,
                    'reason' => '再接続不可能な状態',
                    'current_status' => $currentLog?->status
                ]);
                return false;
            }

            return DB::transaction(function () use ($userId, $context, $currentLog, $metadata) {
                $connectionMetadata = $this->buildConnectionMetadata($metadata, 'reconnection');

                if ($currentLog && $currentLog->isTemporarilyDisconnected()) {
                    // 切断時間を計算
                    $disconnectionDuration = now()->diffInSeconds($currentLog->disconnected_at);
                    $connectionMetadata['disconnection_duration'] = $disconnectionDuration;

                    // 既存ログを更新
                    $currentLog->update([
                        'status' => ConnectionStatus::CONNECTED,
                        'reconnected_at' => now(),
                        'metadata' => array_merge(
                            (array)$currentLog->metadata,
                            ['reconnection_metadata' => $connectionMetadata]
                        )
                    ]);

                    $this->logWithConfig('info', 'ユーザーが再接続しました', [
                        'userId' => $userId,
                        'context' => $context,
                        'disconnection_duration' => $disconnectionDuration,
                        'log_id' => $currentLog->id
                    ]);
                } else {
                    // 新規接続ログを作成
                    $newLog = ConnectionLog::create([
                        'user_id' => $userId,
                        'context_type' => $context['type'],
                        'context_id' => $context['id'],
                        'status' => ConnectionStatus::CONNECTED,
                        'connected_at' => now(),
                        'metadata' => $connectionMetadata
                    ]);

                    $this->logWithConfig('info', '新規接続ログを作成しました', [
                        'userId' => $userId,
                        'context' => $context,
                        'log_id' => $newLog->id
                    ]);
                }

                return true;
            });
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
     * 最終切断を記録
     *
     * @param int $userId
     * @param array $context
     * @return void
     */
    public function recordFinalDisconnection(int $userId, array $context): void
    {
        try {
            $currentLog = $this->getLatestLog($userId, $context);
            if (!$currentLog) {
                return;
            }

            DB::transaction(function () use ($currentLog, $userId, $context) {
                $currentLog->update([
                    'status' => ConnectionStatus::DISCONNECTED,
                    'metadata' => array_merge(
                        (array)$currentLog->metadata,
                        ['finalized_at' => now()->toDateTimeString()]
                    )
                ]);

                $this->logWithConfig('info', 'ユーザーの切断を確定しました', [
                    'userId' => $userId,
                    'context' => $context,
                    'log_id' => $currentLog->id
                ]);
            });
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'final_disconnection',
                'userId' => $userId,
                'context' => $context
            ]);
        }
    }

    /**
     * ハートビート更新
     *
     * @param int $userId
     * @param array $context
     * @return void
     */
    public function updateHeartbeat(int $userId, array $context): void
    {
        try {
            // ユーザーの存在確認
            if (!$this->validateUser($userId)) {
                return;
            }

            $currentLog = $this->getLatestLog($userId, $context);

            if ($currentLog && $currentLog->isConnected()) {
                // ハートビート更新
                $metadata = (array)$currentLog->metadata;
                $metadata['last_heartbeat'] = now()->toDateTimeString();

                $currentLog->update(['metadata' => $metadata]);

                $this->logWithConfig('debug', 'ハートビートを更新しました', [
                    'userId' => $userId,
                    'context' => $context,
                    'heartbeat' => true
                ]);
            }
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'heartbeat_update',
                'userId' => $userId,
                'context' => $context
            ]);
        }
    }

    /**
     * 最新の接続ログを取得
     *
     * @param int $userId
     * @param array $context
     * @return ConnectionLog|null
     */
    private function getLatestLog(int $userId, array $context): ?ConnectionLog
    {
        return ConnectionLog::getLatestLog($userId, $context['type'], $context['id']);
    }

    /**
     * ユーザーの存在確認
     *
     * @param int $userId
     * @return bool
     */
    private function validateUser(int $userId): bool
    {
        $user = User::withTrashed()->find($userId);
        if (!$user) {
            $this->logWithConfig('warning', '存在しないユーザーによる処理をスキップしました', [
                'userId' => $userId
            ]);
            return false;
        }
        return true;
    }

    /**
     * 接続メタデータを構築
     *
     * @param array $customMetadata
     * @param string $connectionType
     * @return array
     */
    private function buildConnectionMetadata(array $customMetadata, string $connectionType): array
    {
        $baseMetadata = [
            'client_info' => request()->header('User-Agent'),
            'ip_address' => request()->ip(),
            'connection_type' => $connectionType,
            'timestamp' => now()->toDateTimeString()
        ];

        return array_merge($baseMetadata, $customMetadata);
    }
}
