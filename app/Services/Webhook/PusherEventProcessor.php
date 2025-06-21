<?php

namespace App\Services\Webhook;

use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use App\Services\Connection\ConnectionCoordinator;
use Illuminate\Support\Facades\Log;

class PusherEventProcessor
{
    public function __construct(
        private ConnectionCoordinator $connectionCoordinator
    ) {}

    /**
     * イベントを処理する
     */
    public function processEvent(array $event): void
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
    public function handleMemberRemoved(array $event): void
    {
        $channel = $event['channel'];
        $userId = $event['user_id'];
        $context = $this->extractContextFromChannel($channel);

        // ユーザーの存在確認（ソフトデリートされたユーザーも含む）
        if (!$this->validateUser($userId, $channel, $context)) {
            return;
        }

        if (!$context) {
            return;
        }

        // 切断処理をスキップすべきかチェック
        if ($this->shouldSkipDisconnectionHandling($context, $userId)) {
            return;
        }

        $this->connectionCoordinator->handleDisconnection($userId, $context);
    }

    /**
     * メンバー追加イベントを処理する
     */
    public function handleMemberAdded(array $event): void
    {
        $channel = $event['channel'];
        $userId = $event['user_id'];
        $context = $this->extractContextFromChannel($channel);

        // ユーザーの存在確認（ソフトデリートされたユーザーも含む）
        if (!$this->validateUser($userId, $channel, $context)) {
            return;
        }

        if ($context) {
            $this->connectionCoordinator->handleReconnection($userId, $context);
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
     * ユーザーの有効性を確認する
     */
    private function validateUser(int $userId, string $channel, ?array $context): bool
    {
        $user = User::withTrashed()->find($userId);
        if (!$user) {
            Log::warning('存在しないユーザーIDによるイベントをスキップしました', [
                'userId' => $userId,
                'channel' => $channel,
                'context' => $context
            ]);
            return false;
        }

        return true;
    }

    /**
     * 切断処理をスキップすべきかどうかを判定する
     */
    private function shouldSkipDisconnectionHandling(array $context, int $userId): bool
    {
        // ディベート終了後に結果ページへ移動する場合はスキップ
        if ($context['type'] === 'debate') {
            if ($this->isRecentDebateFinish($context['id'], $userId)) {
                return true;
            }
        }

        // 明示的な退出の場合はスキップ
        if ($this->isExplicitExit($context, $userId)) {
            return true;
        }

        return false;
    }

    /**
     * 直近でディベートが終了したかをチェック
     */
    private function isRecentDebateFinish(int $debateId, int $userId): bool
    {
        // 最近（20秒以内）にディベートが終了したかを確認
        $debate = Debate::find($debateId);

        if (!$debate) {
            return false;
        }

        // ディベートが終了状態か確認
        $isFinished = $debate->room->status === Room::STATUS_FINISHED;

        if ($isFinished) {
            Log::info('ディベート終了による退室のため切断処理をスキップ', [
                'userId' => $userId,
                'debateId' => $debateId
            ]);
        }

        return $isFinished;
    }

    /**
     * 明示的な退出かどうかを判定
     */
    private function isExplicitExit(array $context, int $userId): bool
    {
        $isExplicit = false;

        if ($context['type'] === 'room') {
            $room = Room::find($context['id']);
            $isExplicit = $room && $room->status === Room::STATUS_DELETED;
        } elseif ($context['type'] === 'debate') {
            $debate = Debate::find($context['id']);
            $isExplicit = $debate && $debate->room && $debate->room->status === Room::STATUS_DELETED;
        }

        if ($isExplicit) {
            Log::info('明示的な退出のため切断処理をスキップ', [
                'userId' => $userId,
                'context' => $context
            ]);
        }

        return $isExplicit;
    }
}
