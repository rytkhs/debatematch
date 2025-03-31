<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ConnectionManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HeartbeatController extends Controller
{
    protected $connectionManager;

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * ハートビートを処理
     */
    public function store(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => '認証が必要です'], 401);
        }

        $request->validate([
            'context_type' => 'required|string|in:room,debate',
            'context_id' => 'required|integer',
        ]);

        $contextType = $request->input('context_type');
        $contextId = $request->input('context_id');

        try {
            // ConnectionManagerを通じて接続状態を更新
            $this->connectionManager->updateLastSeen($userId, [
                'type' => $contextType,
                'id' => $contextId
            ]);

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            Log::error('ハートビート処理でエラーが発生しました', [
                'userId' => $userId,
                'context' => [
                    'type' => $contextType,
                    'id' => $contextId
                ],
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'ハートビート処理に失敗しました'], 500);
        }
    }
}
