<?php

namespace App\Services\Connection;

use App\Models\ConnectionLog;
use App\Jobs\HandleUserDisconnection;
use App\Services\Connection\Traits\ConnectionErrorHandler;
use Carbon\Carbon;
use App\Models\User;

class DisconnectionHandler
{
    use ConnectionErrorHandler;

    public function __construct(
        private ConnectionStateManager $stateManager,
        private ConnectionLogger $logger
    ) {
        //
    }

    /**
     * ユーザーの切断を処理
     *
     * @param int $userId
     * @param array $context
     * @return \Illuminate\Foundation\Bus\PendingDispatch|null
     */
    public function handle(int $userId, array $context)
    {
        try {
            // ユーザーの存在確認（ソフトデリートされたユーザーも含む）
            $user = User::withTrashed()->find($userId);
            if (!$user) {
                $this->logWithConfig('warning', '存在しないユーザーIDによる切断処理をスキップしました', [
                    'userId' => $userId,
                    'context' => $context
                ]);
                return null;
            }

            // 現在のユーザーの接続状態を確認
            $currentLog = ConnectionLog::getLatestLog($userId, $context['type'], $context['id']);

            // すでに切断処理中の場合は何もしない
            if ($currentLog && $currentLog->isTemporarilyDisconnected()) {
                $this->logWithConfig('info', 'すでに切断処理中のため、新たな切断処理はスキップします', [
                    'userId' => $userId,
                    'context' => $context,
                    'disconnected_at' => $currentLog->disconnected_at,
                    'elapsed_seconds' => now()->diffInSeconds($currentLog->disconnected_at)
                ]);
                return null;
            }

            // 切断記録を作成
            $disconnectionLog = $this->logger->recordDisconnection($userId, $context, [
                'disconnect_type' => 'unintentional'
            ]);

            if (!$disconnectionLog) {
                $this->logWithConfig('warning', '切断記録の作成がスキップされました', [
                    'userId' => $userId,
                    'context' => $context
                ]);
                return null;
            }

            // 猶予期間を計算してジョブをディスパッチ
            $gracePeriod = $this->calculateGracePeriod($context);

            $this->logWithConfig('info', 'ユーザー切断処理を開始しました', [
                'userId' => $userId,
                'context' => $context,
                'grace_period' => $gracePeriod,
                'log_id' => $disconnectionLog->id
            ]);

            return HandleUserDisconnection::dispatch($userId, $context)
                ->delay(Carbon::now()->addSeconds($gracePeriod));
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
     * 異常切断の検知
     *
     * @param int $userId
     * @param array $context
     * @return bool
     */
    public function detectAbnormalDisconnection(int $userId, array $context): bool
    {
        try {
            $recentLogs = ConnectionLog::where('user_id', $userId)
                ->where('context_type', $context['type'])
                ->where('context_id', $context['id'])
                ->where('created_at', '>=', now()->subHours(1))
                ->count();

            $threshold = config('connection.analysis.disconnection_threshold', 5);

            if ($recentLogs >= $threshold) {
                $this->logWithConfig('warning', '異常な切断パターンを検知しました', [
                    'userId' => $userId,
                    'context' => $context,
                    'recent_disconnections' => $recentLogs,
                    'threshold' => $threshold
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'abnormal_disconnection_detection',
                'userId' => $userId,
                'context' => $context
            ]);
            return false;
        }
    }

    /**
     * 永続的な切断処理
     *
     * @param int $userId
     * @param array $context
     * @return void
     */
    public function finalizeDisconnection(int $userId, array $context): void
    {
        try {
            $this->logger->recordFinalDisconnection($userId, $context);

            $this->logWithConfig('info', 'ユーザーの切断を確定しました', [
                'userId' => $userId,
                'context' => $context
            ]);
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'finalize_disconnection',
                'userId' => $userId,
                'context' => $context
            ]);
        }
    }
}
