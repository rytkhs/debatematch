<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomUser;
use App\Models\Room;
use App\Jobs\HandleUserDisconnection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ConnectionManager;
use App\Models\Debate;
use App\Services\DebateService;

class PusherWebhookController extends Controller
{
    protected $connectionManager;
    protected $debateService;

    public function __construct(ConnectionManager $connectionManager, DebateService $debateService)
    {
        $this->connectionManager = $connectionManager;
        $this->debateService = $debateService;
    }

    public function handle(Request $request)
    {
        // Webhook の秘密鍵を用いた署名を検証し、リクエストが正当なものであることを確認
        if (!$this->isValidPusherRequest($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Pusher イベントを処理
        $events = $request->input('events');

        foreach ($events as $event) {
            $this->processEvent($event);
        }

        return response()->json([], 200);
    }

    /**
     * イベントの種類に応じて適切な処理を行う
     */
    private function processEvent(array $event): void
    {
        $eventHandlers = [
            'member_removed' => 'handleMemberRemoved',
            'member_added' => 'handleMemberAdded'
        ];

        $eventName = $event['name'];

        if (isset($eventHandlers[$eventName])) {
            $handlerMethod = $eventHandlers[$eventName];
            $this->$handlerMethod($event);
        }
    }

    /**
     * メンバー削除イベントを処理する
     */
    private function handleMemberRemoved(array $event): void
    {
        $channel = $event['channel'];
        $userId = $event['user_id'];
        $context = $this->extractContextFromChannel($channel);

        if ($context) {
            // 以下の場合は切断処理をスキップ:
            // 1. ルームからディベートへの移動の場合
            // 2. ディベート終了後に結果ページへ移動する場合
            // 3. 明示的な退出（例：ユーザーが手動で退出した場合）

            if ($context['type'] === 'room') {
                // ディベートが直前に開始されたかチェック（ルームからディベートへの移動）
                $recentDebateStarted = $this->checkRecentDebateStart($userId, $context['id']);
                if ($recentDebateStarted) {
                    Log::info('ディベート開始による退室のため切断処理をスキップ', [
                        'userId' => $userId,
                        'roomId' => $context['id']
                    ]);
                    return;
                }
            } elseif ($context['type'] === 'debate') {
                // ディベートが最近終了したかチェック（結果ページへの移動）
                $recentDebateFinished = $this->checkRecentDebateFinish($context['id']);
                if ($recentDebateFinished) {
                    Log::info('ディベート終了による退室のため切断処理をスキップ', [
                        'userId' => $userId,
                        'debateId' => $context['id']
                    ]);
                    return;
                }
            }

            // 明示的な退出かどうかをチェック
            if ($this->isExplicitExit($context)) {
                Log::info('明示的な退出のため切断処理をスキップ', [
                    'userId' => $userId,
                    'context' => $context
                ]);
                return;
            }

            $this->connectionManager->handleDisconnection($userId, $context);
        }
    }

    /**
     * メンバー追加イベントを処理する
     */
    private function handleMemberAdded(array $event): void
    {
        $channel = $event['channel'];
        $userId = $event['user_id'];
        $context = $this->extractContextFromChannel($channel);

        if ($context) {
            $this->connectionManager->handleReconnection($userId, $context);
        }
    }

    /**
     * チャンネル名からコンテキスト情報を抽出する
     */
    private function extractContextFromChannel(string $channel): ?array
    {
        if (strpos($channel, 'presence-room.') === 0) {
            $roomId = (int) str_replace('presence-room.', '', $channel);
            return ['type' => 'room', 'id' => $roomId];
        }

        if (strpos($channel, 'presence-debate.') === 0) {
            $debateId = (int) str_replace('presence-debate.', '', $channel);
            return ['type' => 'debate', 'id' => $debateId];
        }

        return null;
    }

    /**
     * 直近でディベートが開始されたかをチェック
     */
    private function checkRecentDebateStart($userId, $roomId): bool
    {
        // 最近（例：10秒以内）にディベートが開始されたかを確認
        $recentDebate = Debate::where('room_id', $roomId)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->exists();

        return $recentDebate;
    }

    /**
     * 直近でディベートが終了したかをチェック
     */
    private function checkRecentDebateFinish($debateId): bool
    {
        // 最近（20秒以内）にディベートが終了したかを確認
        $debate = Debate::find($debateId);

        if (!$debate) {
            return false;
        }

        // ディベートが終了状態か、または最近終了したか確認
        return $debate->room->status === Room::STATUS_FINISHED ||
            ($debate->finished_at && $debate->finished_at->diffInSeconds(now()) <= 20);
    }

    /**
     * 明示的な退出かどうかを判定
     */
    private function isExplicitExit($context): bool
    {
        if ($context['type'] === 'room') {
            $room = Room::find($context['id']);
            if ($room && $room->status === Room::STATUS_DELETED) {
                return true;
            }
        } else if ($context['type'] === 'debate') {
            $debate = Debate::find($context['id']);
            if ($debate && $debate->room && $debate->room->status === Room::STATUS_DELETED) {
                return true;
            }
        }
        return false;
    }

    private function isValidPusherRequest(Request $request)
    {
        // Pusherのシークレットキーを取得
        $pusherSecret = config('broadcasting.connections.pusher.secret');

        // リクエストボディを取得
        $requestBody = $request->getContent();

        // X-Pusher-Signatureヘッダーを取得
        $pusherSignature = $request->header('X-Pusher-Signature');

        if (empty($pusherSignature)) {
            return false;
        }

        // HMAC SHA256を使用して署名を計算
        $expectedSignature = hash_hmac('sha256', $requestBody, $pusherSecret);

        // 計算された署名と受信した署名を比較
        return hash_equals($expectedSignature, $pusherSignature);
    }
}
