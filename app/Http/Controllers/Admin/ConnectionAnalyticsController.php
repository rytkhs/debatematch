<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConnectionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConnectionAnalyticsController extends Controller
{
    /**
     * 接続分析ダッシュボードを表示
     */
    public function index()
    {
        // 直近24時間の切断統計
        $disconnectionStats = ConnectionLog::select(
            DB::raw('DATE_FORMAT(created_at, "%H:00") as hour'),
            DB::raw('COUNT(*) as count')
        )
            ->where('status', 'disconnected')
            ->where('created_at', '>=', now()->subDay())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // ユーザー別の切断頻度ランキング
        $userDisconnectionRanking = ConnectionLog::select(
            'user_id',
            DB::raw('COUNT(*) as disconnection_count')
        )
            ->where('status', 'disconnected')
            ->where('created_at', '>=', now()->subWeek())
            ->groupBy('user_id')
            ->orderByDesc('disconnection_count')
            ->limit(10)
            ->with('user:id,name')
            ->get();

        // 平均再接続率
        $reconnectionRate = ConnectionLog::where('created_at', '>=', now()->subWeek())
            ->whereNotNull('reconnected_at')
            ->count() / max(1, ConnectionLog::where('status', 'temporarily_disconnected')
                ->where('created_at', '>=', now()->subWeek())
                ->count()) * 100;

        return view('admin.connection_analytics', compact(
            'disconnectionStats',
            'userDisconnectionRanking',
            'reconnectionRate'
        ));
    }

    /**
     * 特定ユーザーの接続履歴詳細
     */
    public function userDetail(User $user)
    {
        $connectionLogs = ConnectionLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        $connectionIssues = ConnectionLog::analyzeConnectionIssues($user->id);

        return view('admin.user_connection_detail', compact(
            'user',
            'connectionLogs',
            'connectionIssues'
        ));
    }
}
