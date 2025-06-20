<?php

namespace App\Services\Connection;

use App\Models\ConnectionLog;
use App\Services\Connection\Traits\ConnectionErrorHandler;
use App\Models\User;

class ReconnectionHandler
{
    use ConnectionErrorHandler;

    public function __construct(
        private ConnectionStateManager $stateManager,
        private ConnectionLogger $logger
    ) {
        //
    }

    /**
     * ユーザーの再接続を処理
     *
     * @param int $userId
     * @param array $context
     * @return bool
     */
    public function handle(int $userId, array $context): bool
    {
        try {
            // ユーザーの存在確認（ソフトデリートされたユーザーも含む）
            $user = User::withTrashed()->find($userId);
            if (!$user) {
                $this->logWithConfig('warning', '存在しないユーザーIDによる再接続処理をスキップしました', [
                    'userId' => $userId,
                    'context' => $context
                ]);
                return false;
            }

            // 最新の接続状態を確認
            $currentLog = ConnectionLog::getLatestLog($userId, $context['type'], $context['id']);

            // 再接続可能性を検証
            if (!$this->validateReconnection($currentLog, $context)) {
                return false;
            }

            // すでに接続済みの場合は何もしない
            if ($currentLog && $currentLog->isConnected()) {
                $this->logWithConfig('info', 'すでに接続済みのため、再接続処理はスキップします', [
                    'userId' => $userId,
                    'context' => $context
                ]);
                return false;
            }

            // 重複再接続防止チェック
            if ($this->isReconnectionInProgress($userId, $context)) {
                $this->logWithConfig('info', '再接続処理が進行中のため、重複処理をスキップします', [
                    'userId' => $userId,
                    'context' => $context
                ]);
                return false;
            }

            // 再接続記録を作成
            $success = $this->logger->recordReconnection($userId, $context, [
                'connection_type' => 'reconnection'
            ]);

            if ($success) {
                $this->logWithConfig('info', 'ユーザーの再接続処理が完了しました', [
                    'userId' => $userId,
                    'context' => $context
                ]);
            }

            return $success;
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
     * 再接続可能性の詳細検証
     *
     * @param ConnectionLog|null $log
     * @param array $context
     * @return bool
     */
    private function validateReconnection(?ConnectionLog $log, array $context): bool
    {
        // 基本的な再接続可能性チェック
        if (!$this->stateManager->canReconnect($log)) {
            $this->logWithConfig('info', '再接続不可能な状態です', [
                'context' => $context,
                'current_status' => $log?->status,
                'reason' => '状態遷移不可'
            ]);
            return false;
        }

        // 一時切断ログが存在し、猶予期間内かどうかを確認
        if ($log && $log->isTemporarilyDisconnected()) {
            $gracePeriod = $this->calculateGracePeriod($context);
            $elapsedSeconds = now()->diffInSeconds($log->disconnected_at);

            if ($elapsedSeconds > $gracePeriod) {
                $this->logWithConfig('warning', '猶予期間を超過した再接続試行', [
                    'context' => $context,
                    'elapsed_seconds' => $elapsedSeconds,
                    'grace_period' => $gracePeriod
                ]);
                // 猶予期間は超過しているが、再接続は許可（ログ出力のみ）
            }
        }

        return true;
    }

    /**
     * 重複再接続防止チェック
     *
     * @param int $userId
     * @param array $context
     * @return bool
     */
    private function isReconnectionInProgress(int $userId, array $context): bool
    {
        // 最近の短時間内での複数再接続試行をチェック
        $recentReconnections = ConnectionLog::where('user_id', $userId)
            ->where('context_type', $context['type'])
            ->where('context_id', $context['id'])
            ->where('reconnected_at', '>=', now()->subSeconds(30))
            ->count();

        return $recentReconnections > 0;
    }

    /**
     * 猶予期間を計算
     *
     * @param array $context
     * @return int
     */
    private function calculateGracePeriod(array $context): int
    {
        $contextType = $context['type'] ?? 'room';

        return $contextType === 'debate'
            ? config('connection.grace_periods.debate', 300)
            : config('connection.grace_periods.room', 840);
    }

    /**
     * 再接続メタデータの管理
     *
     * @param ConnectionLog $log
     * @param array $additionalMetadata
     * @return array
     */
    public function buildReconnectionMetadata(ConnectionLog $log, array $additionalMetadata = []): array
    {
        $metadata = [
            'reconnection_timestamp' => now()->toISOString(),
            'disconnection_duration' => $log->disconnected_at
                ? $log->disconnected_at->diffInSeconds(now())
                : null,
            'previous_status' => $log->status,
            'client_info' => request()->header('User-Agent'),
            'ip_address' => request()->ip()
        ];

        return array_merge($metadata, $additionalMetadata);
    }

    /**
     * 再接続統計の取得
     *
     * @param int $userId
     * @param array $context
     * @param int $hours
     * @return array
     */
    public function getReconnectionStats(int $userId, array $context, int $hours = 24): array
    {
        try {
            $logs = ConnectionLog::where('user_id', $userId)
                ->where('context_type', $context['type'])
                ->where('context_id', $context['id'])
                ->where('created_at', '>=', now()->subHours($hours))
                ->whereNotNull('reconnected_at')
                ->get();

            $reconnectionCount = $logs->count();
            $averageDuration = $logs->avg(function ($log) {
                return $log->disconnected_at && $log->reconnected_at
                    ? $log->disconnected_at->diffInSeconds($log->reconnected_at)
                    : null;
            });

            return [
                'reconnection_count' => $reconnectionCount,
                'average_disconnection_duration' => round($averageDuration ?? 0, 2),
                'analysis_period_hours' => $hours
            ];
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'reconnection_stats',
                'userId' => $userId,
                'context' => $context
            ]);
            return [
                'reconnection_count' => 0,
                'average_disconnection_duration' => 0,
                'analysis_period_hours' => $hours,
                'error' => true
            ];
        }
    }
}
