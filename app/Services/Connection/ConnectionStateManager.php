<?php

namespace App\Services\Connection;

use App\Models\ConnectionLog;
use App\Enums\ConnectionStatus;
use App\Services\Connection\Traits\ConnectionErrorHandler;

class ConnectionStateManager
{
    use ConnectionErrorHandler;

    /**
     * 有効な状態遷移のマッピング
     */
    private array $validTransitions = [
        ConnectionStatus::CONNECTED => [
            ConnectionStatus::TEMPORARILY_DISCONNECTED,
            ConnectionStatus::GRACEFULLY_DISCONNECTED
        ],
        ConnectionStatus::TEMPORARILY_DISCONNECTED => [
            ConnectionStatus::CONNECTED,
            ConnectionStatus::DISCONNECTED
        ],
        ConnectionStatus::DISCONNECTED => [],
        ConnectionStatus::GRACEFULLY_DISCONNECTED => []
    ];

    /**
     * 状態遷移が有効かどうかを検証
     *
     * @param string $fromStatus
     * @param string $toStatus
     * @return bool
     */
    public function validateTransition(string $fromStatus, string $toStatus): bool
    {
        if (!isset($this->validTransitions[$fromStatus])) {
            return false;
        }

        return in_array($toStatus, $this->validTransitions[$fromStatus]);
    }

    /**
     * 接続ログの状態遷移を検証
     *
     * @param ConnectionLog|null $currentLog
     * @param string $newStatus
     * @return bool
     */
    public function validateLogTransition(?ConnectionLog $currentLog, string $newStatus): bool
    {
        // 初回接続（ログがない場合）
        if (!$currentLog) {
            return $newStatus === ConnectionStatus::CONNECTED;
        }

        return $this->validateTransition($currentLog->status, $newStatus);
    }

    /**
     * 再接続が可能な状態かどうかを確認
     *
     * @param ConnectionLog|null $log
     * @return bool
     */
    public function canReconnect(?ConnectionLog $log): bool
    {
        if (!$log) {
            return true; // 初回接続扱い
        }

        return $log->status === ConnectionStatus::TEMPORARILY_DISCONNECTED;
    }

    /**
     * 切断処理が可能な状態かどうかを確認
     *
     * @param ConnectionLog|null $log
     * @return bool
     */
    public function canDisconnect(?ConnectionLog $log): bool
    {
        // ログがない、または既に切断中の場合は処理不要
        if (!$log || $log->isTemporarilyDisconnected()) {
            return false;
        }

        return $log->isConnected();
    }

    /**
     * 有効な接続状態のリストを取得
     *
     * @return array
     */
    public function getValidStatuses(): array
    {
        return [
            ConnectionStatus::CONNECTED,
            ConnectionStatus::TEMPORARILY_DISCONNECTED,
            ConnectionStatus::DISCONNECTED,
            ConnectionStatus::GRACEFULLY_DISCONNECTED
        ];
    }

    /**
     * 状態の説明を取得
     *
     * @param string $status
     * @return string
     */
    public function getStatusDescription(string $status): string
    {
        $descriptions = [
            ConnectionStatus::CONNECTED => '接続中',
            ConnectionStatus::TEMPORARILY_DISCONNECTED => '一時的切断',
            ConnectionStatus::DISCONNECTED => '切断済み',
            ConnectionStatus::GRACEFULLY_DISCONNECTED => '正常切断'
        ];

        return $descriptions[$status] ?? '不明な状態';
    }

    /**
     * 状態が終了状態かどうかを確認
     *
     * @param string $status
     * @return bool
     */
    public function isFinalStatus(string $status): bool
    {
        return in_array($status, [
            ConnectionStatus::DISCONNECTED,
            ConnectionStatus::GRACEFULLY_DISCONNECTED
        ]);
    }
}
