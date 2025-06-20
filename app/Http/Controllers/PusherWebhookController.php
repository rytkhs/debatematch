<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Webhook\PusherEventProcessor;
use Illuminate\Support\Facades\Log;

class PusherWebhookController extends Controller
{
    public function __construct(
        private PusherEventProcessor $eventProcessor
    ) {}

    public function handle(Request $request)
    {
        // Webhook の秘密鍵を用いた署名を検証し、リクエストが正当なものであることを確認
        if (!$this->isValidPusherRequest($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Pusher イベントを処理
        $events = $request->input('events');

        foreach ($events as $event) {
            $this->eventProcessor->processEvent($event);
        }

        return response()->json([], 200);
    }

    /**
     * Pusherリクエストの署名を検証する
     */
    private function isValidPusherRequest(Request $request): bool
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
