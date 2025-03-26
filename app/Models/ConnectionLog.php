<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Services\ConnectionManager;

class ConnectionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'context_type',
        'context_id',
        'status',
        'connected_at',
        'disconnected_at',
        'reconnected_at',
        'metadata'
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'reconnected_at' => 'datetime',
        'metadata' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 最新の接続ログを取得する
     *
     * @param int $userId
     * @param string $contextType
     * @param int $contextId
     * @return ConnectionLog|null
     */
    public static function getLatestLog($userId, $contextType, $contextId)
    {
        return self::where('user_id', $userId)
            ->where('context_type', $contextType)
            ->where('context_id', $contextId)
            ->latest()
            ->first();
    }

    /**
     * 接続状態が「接続中」かどうかを確認する
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->status === ConnectionManager::STATUS_CONNECTED;
    }

    /**
     * 接続状態が「一時的に切断」かどうかを確認する
     *
     * @return bool
     */
    public function isTemporarilyDisconnected()
    {
        return $this->status === ConnectionManager::STATUS_TEMPORARILY_DISCONNECTED;
    }

    /**
     * 特定のコンテキストで現在接続中のユーザーのIDを取得する
     *
     * @param string $contextType
     * @param int $contextId
     * @return array
     */
    public static function getConnectedUserIds($contextType, $contextId)
    {
        return self::select('user_id')
            ->where('context_type', $contextType)
            ->where('context_id', $contextId)
            ->where('status', ConnectionManager::STATUS_CONNECTED)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('connection_logs as newer')
                    ->whereRaw('connection_logs.user_id = newer.user_id')
                    ->whereRaw('connection_logs.context_type = newer.context_type')
                    ->whereRaw('connection_logs.context_id = newer.context_id')
                    ->whereRaw('connection_logs.id < newer.id');
            })
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * ユーザーの接続継続時間を計算（秒単位）
     *
     * @return int|null
     */
    public function getConnectionDuration()
    {
        // 接続中の場合は現在時刻までの時間を計算
        if ($this->status === ConnectionManager::STATUS_CONNECTED) {
            return $this->connected_at ? now()->diffInSeconds($this->connected_at) : null;
        }

        // 切断された場合は接続から切断までの時間を計算
        if ($this->disconnected_at && $this->connected_at) {
            return $this->disconnected_at->diffInSeconds($this->connected_at);
        }

        return null;
    }

    /**
     * 特定期間内の接続エラー頻度を分析
     *
     * @param int $userId
     * @param int $hours 分析する時間枠（時間単位）
     * @return array 分析結果
     */
    public static function analyzeConnectionIssues($userId, $hours = 24)
    {
        $startTime = now()->subHours($hours);

        $disconnections = self::where('user_id', $userId)
            ->where('created_at', '>=', $startTime)
            ->whereIn('status', [
                ConnectionManager::STATUS_TEMPORARILY_DISCONNECTED,
                ConnectionManager::STATUS_DISCONNECTED
            ])
            ->count();

        $reconnections = self::where('user_id', $userId)
            ->where('created_at', '>=', $startTime)
            ->whereNotNull('reconnected_at')
            ->count();

        return [
            'total_disconnections' => $disconnections,
            'successful_reconnections' => $reconnections,
            'failure_rate' => $disconnections > 0 ?
                (($disconnections - $reconnections) / $disconnections) * 100 : 0
        ];
    }

    /**
     * 特定のコンテキストに対するユーザーの初回接続を記録する
     *
     * @param int $userId
     * @param string $contextType
     * @param int $contextId
     * @return ConnectionLog
     */
    public static function recordInitialConnection($userId, $contextType, $contextId)
    {
        // すでにログが存在するか確認
        $existingLog = self::getLatestLog($userId, $contextType, $contextId);
        if ($existingLog && $existingLog->isConnected()) {
            return $existingLog;
        }

        // クライアント情報の記録
        $clientInfo = request()->header('User-Agent');
        $ipAddress = request()->ip();

        // 初回接続ログの作成
        return self::create([
            'user_id' => $userId,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'status' => ConnectionManager::STATUS_CONNECTED,
            'connected_at' => now(),
            'metadata' => [
                'client_info' => $clientInfo,
                'ip_address' => $ipAddress,
                'connection_type' => 'initial'
            ]
        ]);
    }
}
