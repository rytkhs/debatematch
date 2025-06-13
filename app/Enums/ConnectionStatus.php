<?php

namespace App\Enums;

/**
 * 接続状態の定数定義
 */
class ConnectionStatus
{
    const CONNECTED = 'connected';
    const TEMPORARILY_DISCONNECTED = 'temporarily_disconnected';
    const DISCONNECTED = 'disconnected';
    const GRACEFULLY_DISCONNECTED = 'gracefully_disconnected';

    /**
     * 全ての有効な状態を取得
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            self::CONNECTED,
            self::TEMPORARILY_DISCONNECTED,
            self::DISCONNECTED,
            self::GRACEFULLY_DISCONNECTED
        ];
    }

    /**
     * 状態の説明を取得
     *
     * @param string $status
     * @return string
     */
    public static function getDescription(string $status): string
    {
        $descriptions = [
            self::CONNECTED => '接続中',
            self::TEMPORARILY_DISCONNECTED => '一時的切断',
            self::DISCONNECTED => '切断済み',
            self::GRACEFULLY_DISCONNECTED => '正常切断'
        ];

        return $descriptions[$status] ?? '不明';
    }

    /**
     * 最終状態かどうか判定
     *
     * @param string $status
     * @return bool
     */
    public static function isFinalStatus(string $status): bool
    {
        return in_array($status, [
            self::DISCONNECTED,
            self::GRACEFULLY_DISCONNECTED
        ]);
    }
}
