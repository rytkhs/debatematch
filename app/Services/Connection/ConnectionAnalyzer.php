<?php

namespace App\Services\Connection;

use App\Models\ConnectionLog;
use App\Services\Connection\Traits\ConnectionErrorHandler;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ConnectionAnalyzer
{
    use ConnectionErrorHandler;

    private const CACHE_PREFIX = 'connection_analysis:';
    private const CACHE_TTL = 300; // 5分間

    /**
     * 基本的な接続統計を取得
     *
     * @param int $userId
     * @param int $hours
     * @return array
     */
    public function getBasicConnectionStats(int $userId, int $hours = 24): array
    {
        try {
            $cacheKey = self::CACHE_PREFIX . "basic_stats:{$userId}:{$hours}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $hours) {
                $from = now()->subHours($hours);

                $logs = ConnectionLog::where('user_id', $userId)
                    ->where('created_at', '>=', $from)
                    ->get();

                $totalConnections = $logs->count();
                $disconnections = $logs->where('status', 'temporarily_disconnected')->count();
                $reconnections = $logs->whereNotNull('reconnected_at')->count();
                $finalDisconnections = $logs->where('status', 'disconnected')->count();

                // 平均切断時間を計算
                $avgDisconnectionDuration = $logs
                    ->filter(function ($log) {
                        return $log->disconnected_at && $log->reconnected_at;
                    })
                    ->avg(function ($log) {
                        return $log->reconnected_at->diffInSeconds($log->disconnected_at);
                    });

                return [
                    'total_connections' => $totalConnections,
                    'disconnections' => $disconnections,
                    'reconnections' => $reconnections,
                    'final_disconnections' => $finalDisconnections,
                    'reconnection_rate' => $disconnections > 0 ? round(($reconnections / $disconnections) * 100, 2) : 0,
                    'average_disconnection_duration' => round($avgDisconnectionDuration ?? 0, 2),
                    'analysis_period_hours' => $hours,
                    'generated_at' => now()->toISOString()
                ];
            });
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'basic_connection_stats',
                'userId' => $userId,
                'hours' => $hours
            ]);

            return $this->getEmptyStats($hours);
        }
    }

    /**
     * 異常パターンを検知
     *
     * @param int $userId
     * @param array $context
     * @return array
     */
    public function detectAnomalousPatterns(int $userId, array $context): array
    {
        try {
            $patterns = [];
            $windowHours = config('connection.analysis.analysis_window_hours', 1);

            // 頻繁な切断パターンの検知
            $frequentDisconnections = $this->detectFrequentDisconnections($userId, $context, $windowHours);
            if ($frequentDisconnections['is_anomalous']) {
                $patterns[] = $frequentDisconnections;
            }

            // 短時間再接続パターンの検知
            $rapidReconnections = $this->detectRapidReconnections($userId, $context, $windowHours);
            if ($rapidReconnections['is_anomalous']) {
                $patterns[] = $rapidReconnections;
            }

            // 異常に長い切断時間の検知
            $prolongedDisconnections = $this->detectProlongedDisconnections($userId, $context, $windowHours);
            if ($prolongedDisconnections['is_anomalous']) {
                $patterns[] = $prolongedDisconnections;
            }

            return [
                'user_id' => $userId,
                'context' => $context,
                'patterns_detected' => count($patterns),
                'anomalous_patterns' => $patterns,
                'analysis_window_hours' => $windowHours,
                'analyzed_at' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'anomalous_pattern_detection',
                'userId' => $userId,
                'context' => $context
            ]);

            return [
                'user_id' => $userId,
                'context' => $context,
                'patterns_detected' => 0,
                'anomalous_patterns' => [],
                'error' => true
            ];
        }
    }

    /**
     * 頻繁な切断パターンを検知
     *
     * @param int $userId
     * @param array $context
     * @param int $hours
     * @return array
     */
    private function detectFrequentDisconnections(int $userId, array $context, int $hours): array
    {
        $disconnections = ConnectionLog::where('user_id', $userId)
            ->where('context_type', $context['type'])
            ->where('context_id', $context['id'])
            ->where('status', 'temporarily_disconnected')
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();

        $threshold = config('connection.analysis.disconnection_threshold', 5);
        $isAnomalous = $disconnections >= $threshold;

        return [
            'pattern_type' => 'frequent_disconnections',
            'is_anomalous' => $isAnomalous,
            'disconnection_count' => $disconnections,
            'threshold' => $threshold,
            'severity' => $this->calculateSeverity($disconnections, $threshold),
            'description' => "過去{$hours}時間で{$disconnections}回の切断が発生"
        ];
    }

    /**
     * 短時間再接続パターンを検知
     *
     * @param int $userId
     * @param array $context
     * @param int $hours
     * @return array
     */
    private function detectRapidReconnections(int $userId, array $context, int $hours): array
    {
        $rapidReconnections = ConnectionLog::where('user_id', $userId)
            ->where('context_type', $context['type'])
            ->where('context_id', $context['id'])
            ->whereNotNull('reconnected_at')
            ->where('created_at', '>=', now()->subHours($hours))
            ->get()
            ->filter(function ($log) {
                return $log->disconnected_at && $log->reconnected_at
                    && $log->reconnected_at->diffInSeconds($log->disconnected_at) < 30;
            })
            ->count();

        $threshold = 3; // 30秒以内の再接続が3回以上
        $isAnomalous = $rapidReconnections >= $threshold;

        return [
            'pattern_type' => 'rapid_reconnections',
            'is_anomalous' => $isAnomalous,
            'rapid_reconnection_count' => $rapidReconnections,
            'threshold' => $threshold,
            'severity' => $this->calculateSeverity($rapidReconnections, $threshold),
            'description' => "過去{$hours}時間で{$rapidReconnections}回の短時間再接続が発生"
        ];
    }

    /**
     * 異常に長い切断時間を検知
     *
     * @param int $userId
     * @param array $context
     * @param int $hours
     * @return array
     */
    private function detectProlongedDisconnections(int $userId, array $context, int $hours): array
    {
        $gracePeriod = $context['type'] === 'debate'
            ? config('connection.grace_periods.debate', 300)
            : config('connection.grace_periods.room', 840);

        $prolongedDisconnections = ConnectionLog::where('user_id', $userId)
            ->where('context_type', $context['type'])
            ->where('context_id', $context['id'])
            ->where('status', 'temporarily_disconnected')
            ->where('created_at', '>=', now()->subHours($hours))
            ->whereNotNull('disconnected_at')
            ->get()
            ->filter(function ($log) use ($gracePeriod) {
                return $log->disconnected_at
                    && $log->disconnected_at->diffInSeconds(now()) > ($gracePeriod * 2);
            })
            ->count();

        $isAnomalous = $prolongedDisconnections > 0;

        return [
            'pattern_type' => 'prolonged_disconnections',
            'is_anomalous' => $isAnomalous,
            'prolonged_disconnection_count' => $prolongedDisconnections,
            'grace_period' => $gracePeriod,
            'severity' => $isAnomalous ? 'high' : 'none',
            'description' => "過去{$hours}時間で{$prolongedDisconnections}回の長時間切断が発生"
        ];
    }

    /**
     * 深刻度を計算
     *
     * @param int $count
     * @param int $threshold
     * @return string
     */
    private function calculateSeverity(int $count, int $threshold): string
    {
        if ($count >= $threshold * 3) {
            return 'critical';
        } elseif ($count >= $threshold * 2) {
            return 'high';
        } elseif ($count >= $threshold) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * 接続品質スコアを計算
     *
     * @param int $userId
     * @param array $context
     * @param int $hours
     * @return array
     */
    public function calculateConnectionQualityScore(int $userId, array $context, int $hours = 24): array
    {
        try {
            $stats = $this->getBasicConnectionStats($userId, $hours);
            $anomalies = $this->detectAnomalousPatterns($userId, $context);

            // 基本スコア（100点満点）
            $baseScore = 100;

            // 切断回数によるペナルティ
            $disconnectionPenalty = min($stats['disconnections'] * 5, 50);

            // 再接続率によるボーナス
            $reconnectionBonus = $stats['reconnection_rate'] > 80 ? 10 : 0;

            // 異常パターンによるペナルティ
            $anomalyPenalty = count($anomalies['anomalous_patterns']) * 15;

            $finalScore = max(0, $baseScore - $disconnectionPenalty + $reconnectionBonus - $anomalyPenalty);

            return [
                'user_id' => $userId,
                'context' => $context,
                'quality_score' => round($finalScore, 2),
                'score_breakdown' => [
                    'base_score' => $baseScore,
                    'disconnection_penalty' => $disconnectionPenalty,
                    'reconnection_bonus' => $reconnectionBonus,
                    'anomaly_penalty' => $anomalyPenalty
                ],
                'quality_level' => $this->getQualityLevel($finalScore),
                'stats' => $stats,
                'anomalies' => $anomalies,
                'calculated_at' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            $this->handleConnectionError($e, [
                'operation' => 'connection_quality_score',
                'userId' => $userId,
                'context' => $context
            ]);

            return [
                'user_id' => $userId,
                'context' => $context,
                'quality_score' => 0,
                'quality_level' => 'unknown',
                'error' => true
            ];
        }
    }

    /**
     * 品質レベルを取得
     *
     * @param float $score
     * @return string
     */
    private function getQualityLevel(float $score): string
    {
        if ($score >= 90) {
            return 'excellent';
        } elseif ($score >= 70) {
            return 'good';
        } elseif ($score >= 50) {
            return 'fair';
        } elseif ($score >= 30) {
            return 'poor';
        }
        return 'critical';
    }

    /**
     * 空の統計データを取得
     *
     * @param int $hours
     * @return array
     */
    private function getEmptyStats(int $hours): array
    {
        return [
            'total_connections' => 0,
            'disconnections' => 0,
            'reconnections' => 0,
            'final_disconnections' => 0,
            'reconnection_rate' => 0,
            'average_disconnection_duration' => 0,
            'analysis_period_hours' => $hours,
            'error' => true
        ];
    }

    /**
     * キャッシュをクリア
     *
     * @param int|null $userId
     */
    public function clearCache(?int $userId = null): void
    {
        if ($userId) {
            // 特定ユーザーのキャッシュをクリア
            $pattern = self::CACHE_PREFIX . "*:{$userId}:*";
        } else {
            // 全キャッシュをクリア
            $pattern = self::CACHE_PREFIX . "*";
        }

        // 実際のキャッシュクリア実装（Redis使用時）
        // Cache::flush() または特定パターンのクリア
    }
}
