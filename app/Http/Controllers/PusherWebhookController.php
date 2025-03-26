<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomUser;
use App\Models\Room;
use App\Jobs\HandleUserDisconnection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ConnectionManager;

class PusherWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Webhook の秘密鍵を用いた署名を検証し、リクエストが正当なものであることを確認
        if (!$this->isValidPusherRequest($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Pusher イベントを処理
        $events = $request->input('events');
        $connectionManager = app(ConnectionManager::class);

        foreach ($events as $event) {
            $this->processEvent($event, $connectionManager);
        }

        return response()->json([], 200);
    }

    /**
     * イベントの種類に応じて適切な処理を行う
     */
    private function processEvent(array $event, ConnectionManager $connectionManager): void
    {
        $eventHandlers = [
            'member_removed' => 'handleMemberRemoved',
            'member_added' => 'handleMemberAdded'
        ];

        $eventName = $event['name'];

        if (isset($eventHandlers[$eventName])) {
            $handlerMethod = $eventHandlers[$eventName];
            $this->$handlerMethod($event, $connectionManager);
        }
    }

    /**
     * メンバー削除イベントを処理する
     */
    private function handleMemberRemoved(array $event, ConnectionManager $connectionManager): void
    {
        $channel = $event['channel'];
        $userId = $event['user_id'];
        $context = $this->extractContextFromChannel($channel);

        if ($context) {
            $connectionManager->handleDisconnection($userId, $context);
        }
    }

    /**
     * メンバー追加イベントを処理する
     */
    private function handleMemberAdded(array $event, ConnectionManager $connectionManager): void
    {
        $channel = $event['channel'];
        $userId = $event['user_id'];
        $context = $this->extractContextFromChannel($channel);

        if ($context) {
            $connectionManager->handleReconnection($userId, $context);
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
