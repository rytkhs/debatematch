<?php

namespace App\Services\Connection\Traits;

use Illuminate\Support\Facades\Log;
use App\Services\SlackNotifier;

trait ConnectionErrorHandler
{
    /**
     * 接続関連のエラーを統一形式で処理
     *
     * @param \Exception $e
     * @param array $context
     */
    protected function handleConnectionError(\Exception $e, array $context): void
    {
        $operation = $context['operation'] ?? 'unknown';
        $userId = $context['userId'] ?? null;
        $contextData = $context['context'] ?? [];

        $logData = [
            'operation' => $operation,
            'user_id' => $userId,
            'context' => $contextData,
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'timestamp' => now()->toISOString()
        ];

        // デバッグモードでのみスタックトレースを記録
        if (config('app.debug')) {
            $logData['stack_trace'] = $e->getTraceAsString();
        }

        Log::error("Connection {$operation} error", $logData);

        // 重要な操作の場合はアラート送信
        if ($this->isCriticalOperation($operation)) {
            $this->sendAlert($e, $context);
        }
    }

    /**
     * 重要な操作かどうかを判定
     *
     * @param string $operation
     * @return bool
     */
    private function isCriticalOperation(string $operation): bool
    {
        $criticalOperations = config('connection.critical_operations', [
            'debate_disconnection',
            'massive_disconnection',
            'database_error',
            'configuration_error'
        ]);

        return in_array($operation, $criticalOperations);
    }

    /**
     * 重要な接続エラーをSlackに通知
     *
     * @param \Exception $e
     * @param array $context
     */
    private function sendAlert(\Exception $e, array $context): void
    {
        try {
            $slackNotifier = app(SlackNotifier::class);

            $operation = $context['operation'] ?? 'unknown';
            $userId = $context['userId'] ?? 'unknown';

            $message = "🚨 **接続システム重要エラー**\n" .
                "**操作**: {$operation}\n" .
                "**ユーザーID**: {$userId}\n" .
                "**エラー**: {$e->getMessage()}\n" .
                "**時刻**: " . now()->format('Y-m-d H:i:s');

            $slackNotifier->send(
                $message,
                null, // デフォルトチャンネル使用
                'Connection Alert Bot',
                ':warning:'
            );
        } catch (\Exception $slackError) {
            // Slack通知に失敗してもシステムを止めない
            Log::error('Slack通知送信に失敗しました', [
                'original_error' => $e->getMessage(),
                'slack_error' => $slackError->getMessage(),
                'context' => $context
            ]);
        }

        Log::critical('Critical connection error occurred', [
            'error' => $e->getMessage(),
            'context' => $context,
            'requires_attention' => true
        ]);
    }

    /**
     * 設定に応じたログ出力
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    protected function logWithConfig(string $level, string $message, array $context = []): void
    {
        $loggingConfig = config('connection.logging', []);

        // デバッグログの制御
        if ($level === 'debug' && !($loggingConfig['debug_enabled'] ?? false)) {
            return;
        }

        // パフォーマンスログの制御
        if (isset($context['performance']) && !($loggingConfig['performance_logging'] ?? true)) {
            return;
        }

        // ハートビートログの制御
        if (isset($context['heartbeat']) && !($loggingConfig['heartbeat_logging'] ?? false)) {
            return;
        }

        Log::$level($message, $context);
    }
}
