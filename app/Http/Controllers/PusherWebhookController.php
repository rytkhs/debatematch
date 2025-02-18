<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomUser;
use App\Models\Room;
use App\Jobs\HandleUserDisconnection;
use Illuminate\Support\Facades\Log;

class PusherWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Webhook の秘密鍵を用いた署名を検証し、リクエストが正当なものであることを確認
        if (!$this->isValidPusherRequest($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Pusher の認証を検証
        $events = $request->input('events');

        foreach ($events as $event) {

            if ($event['name'] == 'member_removed') {
                $channel = $event['channel']; // presence-room.{roomId}
                $channelParts = explode('.', $channel);
                if (count($channelParts) == 2 && $channelParts[0] == 'presence-room') {
                    $roomId = $channelParts[1];
                    $userId = $event['user_id'];
                    // ユーザーのステータスを '切断' に更新
                    RoomUser::where('room_id', $roomId)
                        ->where('user_id', $userId)
                        ->update(['status' => RoomUser::STATUS_DISCONNECTED, 'last_seen_at' => now()]);
                    // 一定時間後に再接続がなければ退出処理を実行
                    // ジョブをディスパッチし、遅延させて実行する
                    // 切断検知時にジョブをディスパッチ
                    $room = Room::find($roomId);
                    $delay = $room->status === 'debating' ? 10 : 5;
                    HandleUserDisconnection::dispatch($roomId, $userId)
                        ->delay(now()->addSeconds($delay));
                }
            }
        }
        return response()->json([], 200);
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
