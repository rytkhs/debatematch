<?php

namespace Tests\Unit\Livewire;

use Tests\Unit\Livewire\BaseLivewireTest;
use App\Livewire\Debates\Header;
use App\Livewire\Debates\EarlyTermination;
use App\Livewire\Rooms\Participants;
use App\Livewire\FlashMessage;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use Tests\Helpers\MockHelpers;
use Carbon\Carbon;

/**
 * Livewireイベントテスト戦略のサンプル実装
 *
 * 実際のテストではなく、イベントテスト戦略の使用例とベストプラクティスを示すクラス
 */
class LivewireEventStrategySampleTest extends BaseLivewireTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // ディベート設定のMock
        MockHelpers::mockDebateConfigs();
        MockHelpers::mockAIConfigs();
    }

    /**
     * 【サンプル1】基本的なEchoイベントテスト
     * ルーム参加/退出イベントの処理
     */
    public function test_sample_basic_echo_events(): void
    {
        $context = $this->testWithMultiUserRoom(Participants::class);
        $livewire = $context['livewire'];
        $room = $context['room'];
        $users = $context['users'];

        // 新しいユーザーの参加をシミュレーション
        $this->simulateRoomUserEvent(
            $livewire,
            $room->id,
            'joined',
            [
                'user_id' => $users[1]->id,
                'user_name' => $users[1]->name,
                'side' => 'negative',
            ]
        );

        // 参加者情報が更新されることを確認
        $livewire->assertSet('negativeDebater', $users[1]->name);

        // リアルタイムイベントが記録されることを確認
        $this->assertRealtimeEventCount('UserJoinedRoom', 1);
    }

    /**
     * 【サンプル2】複雑なイベントシーケンステスト
     * ディベートターン進行の一連の流れ
     */
    public function test_sample_complex_event_sequence(): void
    {
        $context = $this->testWithDebateContext(Header::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        // イベントシーケンス定義
        $eventSequence = [
            [
                'type' => 'echo-private',
                'channel' => "debate.{$debate->id}",
                'event' => 'TurnAdvanced',
                'data' => [
                    'turn_number' => 1,
                    'speaker' => 'negative',
                    'is_prep_time' => false,
                    'turn_end_time' => now()->addMinutes(6)->timestamp,
                ],
                'assertions' => [
                    'currentTurn' => 1,
                    'currentSpeaker' => 'negative',
                ]
            ],
            [
                'type' => 'echo-private',
                'channel' => "debate.{$debate->id}",
                'event' => 'TurnAdvanced',
                'data' => [
                    'turn_number' => 2,
                    'speaker' => 'affirmative',
                    'is_prep_time' => true,
                    'turn_end_time' => now()->addMinutes(5)->timestamp,
                ],
                'assertions' => [
                    'currentTurn' => 2,
                    'isPrepTime' => true,
                ]
            ]
        ];

        // シーケンス実行
        $this->runEventScenario($livewire, ['events' => $eventSequence]);

        // 最終的な状態確認
        $livewire->assertSet('currentTurn', 2)
            ->assertSet('isPrepTime', true);
    }

    /**
     * 【サンプル3】早期終了イベントの完全なフロー
     * 提案→同意→終了の一連の流れ
     */
    public function test_sample_early_termination_flow(): void
    {
        $context = $this->testWithDebateContext(EarlyTermination::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];
        $user = $context['user'];

        // 1. 早期終了提案
        $this->simulateEarlyTerminationEvent(
            $livewire,
            $debate->id,
            'requested',
            [
                'requester_id' => $user->id,
                'requester_name' => $user->name,
                'requested_at' => now()->timestamp,
            ]
        );

        // 提案が表示されることを確認
        $livewire->assertSet('showEarlyTerminationRequest', true);

        // 2. 相手方の同意
        $this->simulateEarlyTerminationEvent(
            $livewire,
            $debate->id,
            'agreed',
            [
                'agreed_at' => now()->timestamp,
            ]
        );

        // 同意により終了フラグが立つことを確認
        $livewire->assertSet('earlyTerminationAgreed', true);

        // 3. イベント順序の確認
        $this->assertRealtimeEventCount('EarlyTerminationRequested', 1);
        $this->assertRealtimeEventCount('EarlyTerminationAgreed', 1);
    }

    /**
     * 【サンプル4】フラッシュメッセージイベントチェーン
     * 通常→遅延表示のイベント連鎖
     */
    public function test_sample_flash_message_chain(): void
    {
        $livewire = $this->testAsUser(FlashMessage::class);

        // 1. 通常のフラッシュメッセージ
        $this->simulateFlashMessage(
            $livewire,
            'ディベートが開始されました',
            'success'
        );

        $livewire->assertSet('message', 'ディベートが開始されました')
            ->assertSet('type', 'success');

        // 2. 遅延フラッシュメッセージ
        $this->simulateFlashMessage(
            $livewire,
            'ターンが進行しました',
            'info',
            true,
            1500
        );

        $livewire->assertSet('delayedMessage', 'ターンが進行しました')
            ->assertSet('delayedType', 'info')
            ->assertSet('delayTime', 1500);
    }

    /**
     * 【サンプル5】接続ステータスイベントテスト
     * オンライン/オフライン状態の変化
     */
    public function test_sample_connection_status_events(): void
    {
        $context = $this->testWithMultiUserRoom(Participants::class);
        $livewire = $context['livewire'];
        $users = $context['users'];

        // メンバーオンライン
        $this->simulateConnectionEvent(
            $livewire,
            'online',
            [
                'user_id' => $users[1]->id,
                'user_name' => $users[1]->name,
            ]
        );

        // メンバーオフライン
        $this->simulateConnectionEvent(
            $livewire,
            'offline',
            [
                'user_id' => $users[1]->id,
                'user_name' => $users[1]->name,
            ]
        );

        // 接続関連イベントが正しく処理されることを確認
        $events = $this->getDispatchedEvents();
        $this->assertCount(2, $events);
        $this->assertEquals('member-online', $events[0]['event']);
        $this->assertEquals('member-offline', $events[1]['event']);
    }

    /**
     * 【サンプル6】エラーイベント処理テスト
     * ネットワークエラー、タイムアウトエラーの処理
     */
    public function test_sample_error_event_handling(): void
    {
        $livewire = $this->testAsUser(Header::class);

        // ネットワークエラーシミュレーション
        $this->simulateErrorEvent(
            $livewire,
            'network',
            [
                'error_code' => 'CONNECTION_LOST',
                'retry_count' => 3,
            ]
        );

        // タイムアウトエラーシミュレーション
        $this->simulateErrorEvent(
            $livewire,
            'timeout',
            [
                'timeout_duration' => 30,
                'operation' => 'turn_advance',
            ]
        );

        // エラーイベントが記録されることを確認
        $this->assertEventData('error-network', [
            'type' => 'network',
            'error_code' => 'CONNECTION_LOST',
        ]);

        $this->assertEventData('error-timeout', [
            'type' => 'timeout',
            'operation' => 'turn_advance',
        ]);
    }

    /**
     * 【サンプル7】パフォーマンス測定付きイベントテスト
     * イベント処理時間の測定
     */
    public function test_sample_performance_event_testing(): void
    {
        $context = $this->testWithDebateContext(Header::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        // パフォーマンス測定付きでイベントを発火
        $this->dispatchEventWithPerformanceCheck(
            $livewire,
            "echo-private:debate.{$debate->id},TurnAdvanced",
            [
                'turn_number' => 1,
                'speaker' => 'negative',
                'is_prep_time' => false,
                'turn_end_time' => now()->addMinutes(5)->timestamp,
            ],
            0.5 // 0.5秒以内に処理完了を期待
        );

        // パフォーマンス测定は自動でアサートされる
        $livewire->assertSet('currentTurn', 1);
    }

    /**
     * 【サンプル8】イベントタイムスタンプ検証
     * イベント発生時刻の正確性確認
     */
    public function test_sample_event_timestamp_validation(): void
    {
        $livewire = $this->testAsUser(FlashMessage::class);
        $expectedTime = Carbon::now();

        $this->simulateFlashMessage(
            $livewire,
            'タイムスタンプテスト',
            'info'
        );

        // イベントタイムスタンプの検証（5秒の許容範囲）
        $this->assertEventTimestamp('showFlashMessage', $expectedTime, 5);
    }

    /**
     * 【サンプル9】複数チャンネルのEchoイベント
     * 複数のチャンネルからのイベントを同時処理
     */
    public function test_sample_multi_channel_echo_events(): void
    {
        $context = $this->testWithDebateContext(Header::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];
        $room = $context['room'];

        // 複数チャンネルのイベントを同時発火
        $multiChannelEvents = [
            [
                'type' => 'echo-private',
                'channel' => "debate.{$debate->id}",
                'event' => 'TurnAdvanced',
                'data' => ['turn_number' => 1],
            ],
            [
                'type' => 'echo',
                'channel' => "rooms.{$room->id}",
                'event' => 'UserJoinedRoom',
                'data' => ['user_id' => 999, 'user_name' => 'New User'],
            ]
        ];

        $this->dispatchAdvancedEventSequence($livewire, $multiChannelEvents);

        // 両方のイベントが処理されることを確認
        $this->assertRealtimeEventCount('TurnAdvanced', 1);
        $this->assertRealtimeEventCount('UserJoinedRoom', 1);
    }

    /**
     * 【サンプル10】条件分岐イベントシナリオ
     * 状態に応じて異なるイベント処理を行うテスト
     */
    public function test_sample_conditional_event_scenarios(): void
    {
        $context = $this->testWithDebateContext(EarlyTermination::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        // 条件分岐テスト：ディベート進行中の場合のみ早期終了可能
        $this->conditionalTest(
            $livewire,
            function ($livewire) {
                // ディベートが進行中かチェック
                $component = $livewire->instance();
                return $component->debate && $component->debate->status === 'in_progress';
            },
            function ($livewire) use ($debate) {
                // 進行中の場合：早期終了提案可能
                $this->simulateEarlyTerminationEvent(
                    $livewire,
                    $debate->id,
                    'requested'
                );
                $livewire->assertSet('canRequestEarlyTermination', true);
            },
            function ($livewire) {
                // 進行中でない場合：早期終了提案不可
                $livewire->assertSet('canRequestEarlyTermination', false);
            }
        );
    }

    /**
     * 【ベストプラクティス例】包括的イベントテストシナリオ
     * 実際のディベートフローを模倣した統合テスト
     */
    public function test_sample_comprehensive_debate_flow(): void
    {
        $context = $this->testWithDebateContext(Header::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        // 包括的なディベートフローシナリオ
        $comprehensiveScenario = [
            'events' => [
                // 1. ディベート開始
                [
                    'type' => 'echo-private',
                    'channel' => "debate.{$debate->id}",
                    'event' => 'DebateStarted',
                    'data' => ['started_at' => now()->timestamp],
                    'assertions' => ['debateStarted' => true],
                ],
                // 2. 最初のターン開始
                [
                    'type' => 'echo-private',
                    'channel' => "debate.{$debate->id}",
                    'event' => 'TurnAdvanced',
                    'data' => [
                        'turn_number' => 0,
                        'speaker' => 'affirmative',
                        'is_prep_time' => true,
                    ],
                    'assertions' => ['currentTurn' => 0, 'isPrepTime' => true],
                ],
                // 3. 発言ターンに移行
                [
                    'type' => 'echo-private',
                    'channel' => "debate.{$debate->id}",
                    'event' => 'TurnAdvanced',
                    'data' => [
                        'turn_number' => 1,
                        'speaker' => 'affirmative',
                        'is_prep_time' => false,
                    ],
                    'assertions' => ['currentTurn' => 1, 'isPrepTime' => false],
                ],
                // 4. 相手のターンに移行
                [
                    'type' => 'echo-private',
                    'channel' => "debate.{$debate->id}",
                    'event' => 'TurnAdvanced',
                    'data' => [
                        'turn_number' => 2,
                        'speaker' => 'negative',
                        'is_prep_time' => false,
                    ],
                    'assertions' => ['currentTurn' => 2, 'currentSpeaker' => 'negative'],
                ],
            ]
        ];

        // シナリオ実行
        $this->runEventScenario($livewire, $comprehensiveScenario);

        // 最終状態検証
        $livewire->assertSet('currentTurn', 2)
            ->assertSet('currentSpeaker', 'negative')
            ->assertSet('isPrepTime', false);

        // イベント記録検証
        $this->assertRealtimeEventCount('DebateStarted', 1);
        $this->assertRealtimeEventCount('TurnAdvanced', 3);

        // イベントログの確認
        $eventLog = $this->getRealtimeEventLog();
        $this->assertCount(4, $eventLog);
    }
}
