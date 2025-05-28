<?php

namespace App\Services;

use App\Models\ConnectionLog;
use App\Models\RoomUser;
use App\Models\DebateDisconnection;
use App\Jobs\HandleUserDisconnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;

class ConnectionManager
{
    // 接続状態の定数
    const STATUS_CONNECTED = 'connected';
    const STATUS_TEMPORARILY_DISCONNECTED = 'temporarily_disconnected';
    const STATUS_DISCONNECTED = 'disconnected';
    const STATUS_GRACEFULLY_DISCONNECTED = 'gracefully_disconnected';

    // 猶予期間（秒）
    private $roomGracePeriod = 840;
    private $debateGracePeriod = 300;

    /**
     * 新規セッション開始時に初回接続を記録
     *
     * @param int $userId
     * @param array $context
     * @return ConnectionLog
     */
    public function recordInitialConnection($userId, $context)
    {
        try {
            // ユーザーの存在確認（ソフトデリートされたユーザーも含む）
            $user = \App\Models\User::withTrashed()->find($userId);
            if (!$user) {
                Log::warning('存在しないユーザーIDによる初回接続記録をスキップしました', [
                    'userId' => $userId,
                    'context' => $context
                ]);
                return null;
            }

            return ConnectionLog::recordInitialConnection(
                $userId,
                $context['type'],
                $context['id']
            );
        } catch (\Exception $e) {
            Log::error('初回接続記録中にエラーが発生しました', [
                'userId' => $userId,
                'context' => $context,
                'error' => $e->getMessage()
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
            // ユーザーの存在確認（ソフトデリートされたユーザーも含む）
            $user = \App\Models\User::withTrashed()->find($userId);
            if (!$user) {
                Log::warning('存在しないユーザーIDによる切断処理をスキップしました', [
                    'userId' => $userId,
                    'context' => $context
                ]);
                return null;
            }

            // 現在のユーザーの接続状態を確認
            $currentLog = ConnectionLog::getLatestLog($userId, $context['type'], $context['id']);

            // すでに切断処理中の場合は何もしない
            if ($currentLog && $currentLog->isTemporarilyDisconnected()) {
                Log::info('すでに切断処理中のため、新たな切断処理はスキップします', [
                    'userId' => $userId,
                    'context' => $context,
                    'disconnected_at' => $currentLog->disconnected_at,
                    'elapsed_seconds' => now()->diffInSeconds($currentLog->disconnected_at)
                ]);
                return null;
            }

            // 一定期間内の切断回数を確認し、異常検知
            $connectionIssues = ConnectionLog::analyzeConnectionIssues($userId, 1); // 1時間以内
            $frequentDisconnections = $connectionIssues['total_disconnections'] > 5;

            DB::transaction(function () use ($userId, $context, $frequentDisconnections) {
                $connectionIssues = ConnectionLog::analyzeConnectionIssues($userId, 1);
                // 接続情報を記録
                $clientInfo = request()->header('User-Agent');
                $ipAddress = request()->ip();

                // 切断ログを記録（拡張メタデータ付き）
                ConnectionLog::create([
                    'user_id' => $userId,
                    'context_type' => $context['type'],
                    'context_id' => $context['id'],
                    'status' => self::STATUS_TEMPORARILY_DISCONNECTED,
                    'disconnected_at' => Carbon::now(),
                    'metadata' => [
                        'client_info' => $clientInfo,
                        'ip_address' => $ipAddress,
                        'frequent_disconnections' => $frequentDisconnections,
                        'disconnect_type' => 'unintentional'
                    ]
                ]);

                // 異常接続パターンの場合は特別なログを残す
                if ($frequentDisconnections) {
                    Log::warning('ユーザーの接続が異常に不安定です', [
                        'userId' => $userId,
                        'context' => $context,
                        'disconnection_rate' => $connectionIssues
                    ]);
                } else {
                    Log::info('ユーザー切断を記録しました', [
                        'userId' => $userId,
                        'context' => $context
                    ]);
                }
            });

            // 猶予期間の決定
            $gracePeriod = $context['type'] === 'debate'
                ? $this->debateGracePeriod
                : $this->roomGracePeriod;

            // ディベート中の場合、不安定接続に対して猶予期間を延長
            if ($context['type'] === 'debate' && $frequentDisconnections) {
                $gracePeriod = min($gracePeriod * 1.5, 450); // 最大450秒まで
            }

            return HandleUserDisconnection::dispatch($userId, $context)
                ->delay(Carbon::now()->addSeconds($gracePeriod));
        } catch (\Exception $e) {
            Log::error('ユーザー切断処理中にエラーが発生しました', [
                'userId' => $userId,
                'context' => $context,
                'error' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString()
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
    public function handleReconnection($userId, $context)
    {
        try {
            // ユーザーの存在確認（ソフトデリートされたユーザーも含む）
            $user = \App\Models\User::withTrashed()->find($userId);
            if (!$user) {
                Log::warning('存在しないユーザーIDによる再接続処理をスキップしました', [
                    'userId' => $userId,
                    'context' => $context
                ]);
                return false;
            }

            // 最新の接続状態を確認
            $currentLog = ConnectionLog::getLatestLog($userId, $context['type'], $context['id']);

            // すでに接続済みの場合は何もしない
            if ($currentLog && $currentLog->isConnected()) {
                Log::info('すでに接続済みのため、再接続処理はスキップします', [
                    'userId' => $userId,
                    'context' => $context
                ]);
                return false;
            }

            DB::transaction(function () use ($userId, $context, $currentLog) {
                // クライアント情報を取得
                $clientInfo = request()->header('User-Agent');
                $ipAddress = request()->ip();

                $metadata = [
                    'client_info' => $clientInfo,
                    'ip_address' => $ipAddress,
                    'connection_type' => 'reconnection'
                ];

                if ($currentLog && $currentLog->isTemporarilyDisconnected()) {
                    // 一時切断からの再接続時間を計算
                    $disconnectionDuration = now()->diffInSeconds($currentLog->disconnected_at);
                    $metadata['disconnection_duration'] = $disconnectionDuration;

                    // 既存の一時切断ログを更新
                    $currentLog->update([
                        'status' => self::STATUS_CONNECTED,
                        'reconnected_at' => now(),
                        'metadata' => array_merge(
                            (array)$currentLog->metadata,
                            ['reconnection_metadata' => $metadata]
                        )
                    ]);

                    Log::info('ユーザーが再接続しました', [
                        'userId' => $userId,
                        'context' => $context,
                        'disconnectionDuration' => $disconnectionDuration,
                    ]);
                } else {
                    // 新規接続ログを作成（以前のログが見つからない場合）
                    ConnectionLog::create([
                        'user_id' => $userId,
                        'context_type' => $context['type'],
                        'context_id' => $context['id'],
                        'status' => self::STATUS_CONNECTED,
                        'connected_at' => now(),
                        'metadata' => $metadata
                    ]);

                    Log::info('新規接続ログを作成しました（再接続データなし）', [
                        'userId' => $userId,
                        'context' => $context
                    ]);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('ユーザー再接続処理中にエラーが発生しました', [
                'userId' => $userId,
                'context' => $context,
                'error' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString()
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
            DB::transaction(function () use ($userId, $context) {
                // 最新の接続ログを更新
                $log = ConnectionLog::getLatestLog($userId, $context['type'], $context['id']);

                if ($log) {
                    // 永続的な切断として更新
                    $log->update([
                        'status' => self::STATUS_DISCONNECTED,
                        'metadata' => array_merge(
                            (array)$log->metadata,
                            ['finalized_at' => now()->toDateTimeString()]
                        )
                    ]);
                }

                Log::info('ユーザーの切断を確定しました', [
                    'userId' => $userId,
                    'context' => $context
                ]);
            });
        } catch (\Exception $e) {
            Log::error('切断確定処理中にエラーが発生しました', [
                'userId' => $userId,
                'context' => $context,
                'error' => $e->getMessage()
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
            // ユーザーの存在確認（ソフトデリートされたユーザーも含む）
            $user = \App\Models\User::withTrashed()->find($userId);
            if (!$user) {
                Log::warning('存在しないユーザーIDによるハートビート処理をスキップしました', [
                    'userId' => $userId,
                    'context' => $context
                ]);
                return;
            }

            // 最新の接続ログを取得
            $log = ConnectionLog::getLatestLog($userId, $context['type'], $context['id']);

            // 接続ログが存在し、接続中状態の場合
            if ($log && $log->isConnected()) {
                // メタデータの更新
                $metadata = (array)$log->metadata;
                $metadata['last_heartbeat'] = now()->toDateTimeString();

                // 接続ログを更新
                $log->update([
                    'metadata' => $metadata
                ]);

                Log::debug('ハートビートによりユーザーの接続状態を更新しました', [
                    'userId' => $userId,
                    'context' => $context
                ]);
            }
            // 切断状態だがハートビートがある場合は再接続とみなす
            elseif ($log && $log->isTemporarilyDisconnected()) {
                Log::info('ハートビートによりユーザーの再接続を検出', [
                    'userId' => $userId,
                    'context' => $context
                ]);

                // 再接続処理を呼び出す
                $this->handleReconnection($userId, $context);
            }
            // 接続ログがない場合は新規接続として記録
            else {
                Log::info('ハートビートによる新規接続記録', [
                    'userId' => $userId,
                    'context' => $context
                ]);

                // 初回接続記録を作成
                $this->recordInitialConnection($userId, $context);
            }
        } catch (\Exception $e) {
            Log::error('ハートビート処理中にエラーが発生しました', [
                'userId' => $userId,
                'context' => $context,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
