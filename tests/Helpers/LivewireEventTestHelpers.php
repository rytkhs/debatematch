<?php

namespace Tests\Helpers;

use Livewire\Features\SupportTesting\Testable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Livewireイベントテスト専用ヘルパートレイト
 *
 * Echo、Livewire、リアルタイムイベントの包括的なテストサポート
 */
trait LivewireEventTestHelpers
{
    /**
     * @var array 発生したイベントを記録する配列
     */
    protected array $dispatchedEvents = [];

    /**
     * @var array リアルタイムイベントのシミュレーション記録
     */
    protected array $realtimeEventLog = [];

    /**
     * イベントテストのセットアップ
     */
    protected function setupEventTesting(): void
    {
        $this->dispatchedEvents = [];
        $this->realtimeEventLog = [];

        // Laravelイベントのモックとトラッキング
        Event::fake();
        Queue::fake();
    }

    /**
     * Echo（Laravel Echo）イベントのシミュレーション
     *
     * @param Testable $livewire
     * @param string $channel
     * @param string $event
     * @param array $data
     * @return Testable
     */
    protected function simulateEchoEvent(
        Testable $livewire,
        string $channel,
        string $event,
        array $data = []
    ): Testable {
        // Echo event format: echo-{channel},{event}
        $echoEventName = "echo-{$channel},{$event}";

        // イベントログに記録
        $this->realtimeEventLog[] = [
            'type' => 'echo',
            'channel' => $channel,
            'event' => $event,
            'data' => $data,
            'timestamp' => now(),
        ];

        return $livewire->dispatch($echoEventName, $data);
    }

    /**
     * Private channelのEchoイベントシミュレーション
     *
     * @param Testable $livewire
     * @param string $channel
     * @param string $event
     * @param array $data
     * @return Testable
     */
    protected function simulatePrivateEchoEvent(
        Testable $livewire,
        string $channel,
        string $event,
        array $data = []
    ): Testable {
        // Private echo event format: echo-private:{channel},{event}
        $echoEventName = "echo-private:{$channel},{$event}";

        $this->realtimeEventLog[] = [
            'type' => 'echo-private',
            'channel' => $channel,
            'event' => $event,
            'data' => $data,
            'timestamp' => now(),
        ];

        return $livewire->dispatch($echoEventName, $data);
    }

    /**
     * Presence channelのEchoイベントシミュレーション
     *
     * @param Testable $livewire
     * @param string $channel
     * @param string $event
     * @param array $data
     * @return Testable
     */
    protected function simulatePresenceEchoEvent(
        Testable $livewire,
        string $channel,
        string $event,
        array $data = []
    ): Testable {
        // Presence echo event format: echo-presence:{channel},{event}
        $echoEventName = "echo-presence:{$channel},{$event}";

        $this->realtimeEventLog[] = [
            'type' => 'echo-presence',
            'channel' => $channel,
            'event' => $event,
            'data' => $data,
            'timestamp' => now(),
        ];

        return $livewire->dispatch($echoEventName, $data);
    }

    /**
     * 通常のLivewireイベントディスパッチ
     *
     * @param Testable $livewire
     * @param string $event
     * @param array $data
     * @return Testable
     */
    protected function dispatchLivewireEvent(
        Testable $livewire,
        string $event,
        array $data = []
    ): Testable {
        $this->dispatchedEvents[] = [
            'type' => 'livewire',
            'event' => $event,
            'data' => $data,
            'timestamp' => now(),
        ];

        return $livewire->dispatch($event, $data);
    }

    /**
     * 複数のイベントを順次発火（高度なイベント戦略用）
     *
     * @param Testable $livewire
     * @param array $events
     * @return Testable
     */
    protected function dispatchAdvancedEventSequence(Testable $livewire, array $events): Testable
    {
        foreach ($events as $event) {
            $type = $event['type'] ?? 'livewire';
            $eventName = $event['event'];
            $data = $event['data'] ?? [];
            $channel = $event['channel'] ?? null;
            $delay = $event['delay'] ?? 0;

            if ($delay > 0) {
                sleep($delay);
            }

            switch ($type) {
                case 'echo':
                    $this->simulateEchoEvent($livewire, $channel, $eventName, $data);
                    break;
                case 'echo-private':
                    $this->simulatePrivateEchoEvent($livewire, $channel, $eventName, $data);
                    break;
                case 'echo-presence':
                    $this->simulatePresenceEchoEvent($livewire, $channel, $eventName, $data);
                    break;
                default:
                    $this->dispatchLivewireEvent($livewire, $eventName, $data);
                    break;
            }
        }

        return $livewire;
    }

    /**
     * ディベート関連のリアルタイムイベントのセット
     *
     * @param Testable $livewire
     * @param int $debateId
     * @param array $turnData
     * @return Testable
     */
    protected function simulateDebateTurnAdvanced(
        Testable $livewire,
        int $debateId,
        array $turnData = []
    ): Testable {
        $defaultData = [
            'turn_number' => 1,
            'speaker' => 'negative',
            'is_prep_time' => false,
            'turn_end_time' => now()->addMinutes(5)->timestamp,
        ];

        $data = array_merge($defaultData, $turnData);

        return $this->simulatePrivateEchoEvent(
            $livewire,
            "debate.{$debateId}",
            'TurnAdvanced',
            $data
        );
    }

    /**
     * ディベート終了イベントのシミュレーション
     *
     * @param Testable $livewire
     * @param int $debateId
     * @param array $finishData
     * @return Testable
     */
    protected function simulateDebateFinished(
        Testable $livewire,
        int $debateId,
        array $finishData = []
    ): Testable {
        $defaultData = [
            'status' => 'finished',
            'finished_at' => now()->timestamp,
        ];

        $data = array_merge($defaultData, $finishData);

        return $this->simulatePrivateEchoEvent(
            $livewire,
            "debate.{$debateId}",
            'DebateFinished',
            $data
        );
    }

    /**
     * ルーム参加/退出イベントのシミュレーション
     *
     * @param Testable $livewire
     * @param int $roomId
     * @param string $action 'joined' or 'left'
     * @param array $userData
     * @return Testable
     */
    protected function simulateRoomUserEvent(
        Testable $livewire,
        int $roomId,
        string $action,
        array $userData = []
    ): Testable {
        $eventName = $action === 'joined' ? 'UserJoinedRoom' : 'UserLeftRoom';

        $defaultData = [
            'user_id' => 1,
            'user_name' => 'Test User',
            'side' => 'affirmative',
        ];

        $data = array_merge($defaultData, $userData);

        return $this->simulateEchoEvent(
            $livewire,
            "rooms.{$roomId}",
            $eventName,
            $data
        );
    }

    /**
     * 早期終了関連イベントのシミュレーション
     *
     * @param Testable $livewire
     * @param int $debateId
     * @param string $action 'requested', 'agreed', 'declined', 'expired'
     * @param array $terminationData
     * @return Testable
     */
    protected function simulateEarlyTerminationEvent(
        Testable $livewire,
        int $debateId,
        string $action,
        array $terminationData = []
    ): Testable {
        $eventMap = [
            'requested' => 'EarlyTerminationRequested',
            'agreed' => 'EarlyTerminationAgreed',
            'declined' => 'EarlyTerminationDeclined',
            'expired' => 'EarlyTerminationExpired',
        ];

        $eventName = $eventMap[$action] ?? 'EarlyTerminationRequested';

        $defaultData = [
            'requester_id' => 1,
            'requester_name' => 'Test User',
            'requested_at' => now()->timestamp,
        ];

        $data = array_merge($defaultData, $terminationData);

        return $this->simulatePrivateEchoEvent(
            $livewire,
            "debate.{$debateId}",
            $eventName,
            $data
        );
    }

    /**
     * フラッシュメッセージイベントのテスト
     *
     * @param Testable $livewire
     * @param string $message
     * @param string $type
     * @param bool $delayed
     * @param int $delay
     * @return Testable
     */
    protected function simulateFlashMessage(
        Testable $livewire,
        string $message,
        string $type = 'info',
        bool $delayed = false,
        int $delay = 0
    ): Testable {
        if ($delayed) {
            return $livewire->dispatch('showDelayedFlashMessage', $message, $type, $delay);
        } else {
            return $livewire->dispatch('showFlashMessage', $message, $type);
        }
    }

    /**
     * 接続ステータス関連イベントのシミュレーション
     *
     * @param Testable $livewire
     * @param string $action 'lost', 'restored', 'online', 'offline'
     * @param array $connectionData
     * @return Testable
     */
    protected function simulateConnectionEvent(
        Testable $livewire,
        string $action,
        array $connectionData = []
    ): Testable {
        $eventMap = [
            'lost' => 'connection-lost',
            'restored' => 'connection-restored',
            'online' => 'member-online',
            'offline' => 'member-offline',
        ];

        $eventName = $eventMap[$action] ?? 'connection-lost';

        $defaultData = [
            'user_id' => 1,
            'user_name' => 'Test User',
            'timestamp' => now()->timestamp,
        ];

        $data = array_merge($defaultData, $connectionData);

        return $this->dispatchLivewireEvent($livewire, $eventName, $data);
    }

    /**
     * イベント発火の順序検証
     *
     * @param array $expectedOrder イベント名の配列
     * @return void
     */
    protected function assertEventOrder(array $expectedOrder): void
    {
        $actualOrder = array_map(function ($event) {
            return $event['event'] ?? $event['type'];
        }, $this->dispatchedEvents);

        $this->assertEquals($expectedOrder, $actualOrder, 'イベントの発火順序が期待と異なります');
    }

    /**
     * リアルタイムイベントの発生回数検証
     *
     * @param string $eventType
     * @param int $expectedCount
     * @return void
     */
    protected function assertRealtimeEventCount(string $eventType, int $expectedCount): void
    {
        $count = count(array_filter($this->realtimeEventLog, function ($event) use ($eventType) {
            return $event['event'] === $eventType;
        }));

        $this->assertEquals($expectedCount, $count, "リアルタイムイベント '{$eventType}' の発生回数が期待と異なります");
    }

    /**
     * イベントデータの内容検証
     *
     * @param string $eventType
     * @param array $expectedData
     * @return void
     */
    protected function assertEventData(string $eventType, array $expectedData): void
    {
        $found = false;
        foreach ($this->dispatchedEvents as $event) {
            if (($event['event'] ?? '') === $eventType) {
                foreach ($expectedData as $key => $value) {
                    $this->assertArrayHasKey($key, $event['data'], "イベントデータに '{$key}' が含まれていません");
                    $this->assertEquals($value, $event['data'][$key], "イベントデータの '{$key}' の値が期待と異なります");
                }
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, "イベント '{$eventType}' が発生していません");
    }

    /**
     * イベントタイムスタンプの検証
     *
     * @param string $eventType
     * @param Carbon $expectedTime
     * @param int $toleranceSeconds
     * @return void
     */
    protected function assertEventTimestamp(
        string $eventType,
        Carbon $expectedTime,
        int $toleranceSeconds = 5
    ): void {
        foreach ($this->dispatchedEvents as $event) {
            if (($event['event'] ?? '') === $eventType) {
                $eventTime = $event['timestamp'];
                $diff = abs($eventTime->diffInSeconds($expectedTime));

                $this->assertLessThanOrEqual(
                    $toleranceSeconds,
                    $diff,
                    "イベント '{$eventType}' のタイムスタンプが期待と異なります"
                );
                return;
            }
        }

        $this->fail("イベント '{$eventType}' が発生していません");
    }

    /**
     * リアルタイムイベントログの取得
     *
     * @return array
     */
    protected function getRealtimeEventLog(): array
    {
        return $this->realtimeEventLog;
    }

    /**
     * 発生したイベントログの取得
     *
     * @return array
     */
    protected function getDispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }

    /**
     * イベントログのクリア
     *
     * @return void
     */
    protected function clearEventLogs(): void
    {
        $this->dispatchedEvents = [];
        $this->realtimeEventLog = [];
    }

    /**
     * 複雑なイベントシナリオのテスト
     *
     * @param Testable $livewire
     * @param array $scenario
     * @return Testable
     */
    protected function runEventScenario(Testable $livewire, array $scenario): Testable
    {
        foreach ($scenario['events'] as $step) {
            $waitTime = $step['wait'] ?? 0;
            if ($waitTime > 0) {
                sleep($waitTime);
            }

            $this->dispatchAdvancedEventSequence($livewire, [$step]);

            // ステップ後の状態検証
            if (isset($step['assertions'])) {
                foreach ($step['assertions'] as $property => $expectedValue) {
                    $livewire->assertSet($property, $expectedValue);
                }
            }
        }

        return $livewire;
    }

    /**
     * パフォーマンス指標付きイベントテスト
     *
     * @param Testable $livewire
     * @param string $eventName
     * @param array $eventData
     * @param float $maxResponseTimeSeconds
     * @return Testable
     */
    protected function dispatchEventWithPerformanceCheck(
        Testable $livewire,
        string $eventName,
        array $eventData = [],
        float $maxResponseTimeSeconds = 1.0
    ): Testable {
        $startTime = microtime(true);

        $livewire->dispatch($eventName, $eventData);

        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $this->assertLessThan(
            $maxResponseTimeSeconds,
            $responseTime,
            "イベント '{$eventName}' の処理時間が {$maxResponseTimeSeconds} 秒を超えました (実際: {$responseTime} 秒)"
        );

        return $livewire;
    }

    /**
     * エラー処理イベントのテスト
     *
     * @param Testable $livewire
     * @param string $errorType
     * @param array $errorData
     * @return Testable
     */
    protected function simulateErrorEvent(
        Testable $livewire,
        string $errorType,
        array $errorData = []
    ): Testable {
        $eventName = "error-{$errorType}";
        $data = array_merge(['type' => $errorType], $errorData);

        return $this->dispatchLivewireEvent($livewire, $eventName, $data);
    }
}
