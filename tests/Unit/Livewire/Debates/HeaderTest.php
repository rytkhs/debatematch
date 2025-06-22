<?php

namespace Tests\Unit\Livewire\Debates;

use Tests\Unit\Livewire\BaseLivewireTest;
use App\Livewire\Debates\Header;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use App\Services\DebateService;
use Tests\Helpers\MockHelpers;
use Carbon\Carbon;

/**
 * Debates/Headerコンポーネントのテスト
 *
 * ディベートヘッダーの表示、ターン管理、リアルタイム更新をテスト
 */
class HeaderTest extends BaseLivewireTest
{
    protected DebateService $debateService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->debateService = app(DebateService::class);

        // AI設定のMock
        MockHelpers::mockAIConfigs();
        MockHelpers::mockDebateConfigs();

        // ディベート形式の設定をMock
        config([
            'debate.formats.lincoln_douglas' => [
                0 => [
                    'name' => 'affirmative_prep',
                    'speaker' => 'affirmative',
                    'duration' => 300,
                    'is_prep_time' => true,
                ],
                1 => [
                    'name' => 'affirmative_constructive',
                    'speaker' => 'affirmative',
                    'duration' => 360,
                    'is_prep_time' => false,
                ],
                2 => [
                    'name' => 'negative_cross_examination',
                    'speaker' => 'negative',
                    'duration' => 180,
                    'is_prep_time' => false,
                    'is_questions' => true,
                ],
                3 => [
                    'name' => 'negative_prep',
                    'speaker' => 'negative',
                    'duration' => 300,
                    'is_prep_time' => true,
                ],
                4 => [
                    'name' => 'negative_constructive',
                    'speaker' => 'negative',
                    'duration' => 420,
                    'is_prep_time' => false,
                ],
                5 => [
                    'name' => 'affirmative_cross_examination',
                    'speaker' => 'affirmative',
                    'duration' => 180,
                    'is_prep_time' => false,
                    'is_questions' => true,
                ],
                6 => [
                    'name' => 'affirmative_rebuttal',
                    'speaker' => 'affirmative',
                    'duration' => 240,
                    'is_prep_time' => false,
                ],
                7 => [
                    'name' => 'negative_rebuttal',
                    'speaker' => 'negative',
                    'duration' => 360,
                    'is_prep_time' => false,
                ],
            ]
        ]);
    }

    /**
     * 基本的なコンポーネントレンダリングテスト
     */
    public function test_header_component_renders_with_debate(): void
    {
        $context = $this->testWithDebateContext(Header::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        $livewire->assertStatus(200)
            ->assertSet('debate.id', $debate->id)
            ->assertSet('currentTurn', 0);
    }

    /**
     * 肯定側ユーザーのターン状態テスト
     */
    public function test_affirmative_user_turn_state(): void
    {
        $context = $this->testWithDebateContext(Header::class, [], 'affirmative');
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        // 最初のターンは肯定側の準備時間
        $format = $this->debateService->getFormat($debate);
        $firstTurn = $format[0];

        if ($firstTurn['speaker'] === 'affirmative') {
            $livewire->assertSet('isMyTurn', true)
                ->assertSet('isAITurn', false);
        } else {
            $livewire->assertSet('isMyTurn', false);
        }
    }

    /**
     * 否定側ユーザーのターン状態テスト
     */
    public function test_negative_user_turn_state(): void
    {
        $context = $this->testWithDebateContext(Header::class, [], 'negative');
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        // 最初のターンは肯定側なので、否定側はfalse
        $format = $this->debateService->getFormat($debate);
        $firstTurn = $format[0];

        if ($firstTurn['speaker'] === 'negative') {
            $livewire->assertSet('isMyTurn', true);
        } else {
            $livewire->assertSet('isMyTurn', false);
        }
    }

    /**
     * AIディベートでのターン状態テスト
     */
    public function test_ai_debate_turn_state(): void
    {
        $context = $this->testWithDebateContext(Header::class, [], 'affirmative', true);
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        // AIディベートの場合、AIのターンでisAITurnがtrueになることを確認
        $format = $this->debateService->getFormat($debate);
        $firstTurn = $format[0];

        if ($firstTurn['speaker'] === 'negative') {
            // 否定側がAIの場合
            $livewire->assertSet('isAITurn', true)
                ->assertSet('isMyTurn', false);
        } else {
            // 肯定側（ユーザー）のターンの場合
            $livewire->assertSet('isMyTurn', true)
                ->assertSet('isAITurn', false);
        }
    }

    /**
     * ターン進行イベントの処理テスト
     */
    public function test_turn_advanced_event_handling(): void
    {
        $context = $this->testWithDebateContext(Header::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        $newTurnData = [
            'turn_number' => 1,
            'speaker' => 'negative',
            'is_prep_time' => false,
            'turn_end_time' => now()->addMinutes(5)->timestamp,
        ];

        // リアルタイムイベントをシミュレーション
        $this->simulateRealtimeEvent(
            $livewire,
            "private:debate.{$debate->id}",
            'TurnAdvanced',
            $newTurnData
        );

        $livewire->assertSet('currentTurn', 1)
            ->assertSet('currentSpeaker', 'negative')
            ->assertSet('isPrepTime', false);
    }

    /**
     * ターン終了時間の設定テスト
     */
    public function test_turn_end_time_setting(): void
    {
        $context = $this->testWithDebateContext(Header::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        $endTime = now()->addMinutes(3);
        $turnData = [
            'turn_number' => 1,
            'speaker' => 'affirmative',
            'turn_end_time' => $endTime->timestamp,
        ];

        $this->simulateRealtimeEvent(
            $livewire,
            "private:debate.{$debate->id}",
            'TurnAdvanced',
            $turnData
        );

        $this->assertTrue(
            $this->assertTimestampProperty($livewire, 'turnEndTime', $endTime)
        );
    }

    /**
     * 自分のターン時のフラッシュメッセージテスト
     */
    public function test_my_turn_flash_message(): void
    {
        $context = $this->testWithDebateContext(Header::class, [], 'affirmative');
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        // 肯定側ユーザーのターンになるイベントを送信
        $turnData = [
            'turn_number' => 1,
            'speaker' => 'affirmative',
            'is_prep_time' => false,
        ];

        $this->simulateRealtimeEvent(
            $livewire,
            "private:debate.{$debate->id}",
            'TurnAdvanced',
            $turnData
        );

        // フラッシュメッセージが発火されることを確認
        $this->assertEventDispatched($livewire, 'showFlashMessage');
    }

    /**
     * ディベート形式の取得テスト
     */
    public function test_debate_format_retrieval(): void
    {
        $context = $this->testWithDebateContext(Header::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        $format = $this->debateService->getFormat($debate);

        // 形式が正しく取得されることを確認
        $this->assertIsArray($format);
        $this->assertNotEmpty($format);

        // 最初のターンの名前が設定されることを確認
        $firstTurnName = $format[0]['name'] ?? '';
        $livewire->assertSet('currentTurnName', $firstTurnName);
    }

    /**
     * 準備時間の状態テスト
     */
    public function test_prep_time_state(): void
    {
        $context = $this->testWithDebateContext(Header::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        // 準備時間のターンデータ
        $prepTurnData = [
            'turn_number' => 0,
            'speaker' => 'affirmative',
            'is_prep_time' => true,
        ];

        $this->simulateRealtimeEvent(
            $livewire,
            "private:debate.{$debate->id}",
            'TurnAdvanced',
            $prepTurnData
        );

        $livewire->assertSet('isPrepTime', true);
    }

    /**
     * ディベート終了状態のテスト
     */
    public function test_debate_finished_state(): void
    {
        $context = $this->testWithDebateContext(Header::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        // ディベート形式の最後のターンを超えるターン番号
        $format = $this->debateService->getFormat($debate);
        $finalTurnNumber = count($format);

        $finishedTurnData = [
            'turn_number' => $finalTurnNumber,
            'speaker' => null,
        ];

        $this->simulateRealtimeEvent(
            $livewire,
            "private:debate.{$debate->id}",
            'TurnAdvanced',
            $finishedTurnData
        );

        // 終了状態では次のターン名が「終了」になることを確認
        $livewire->assertSet('nextTurnName', __('debates_format.finished'));
    }

    /**
     * コンポーネント状態の同期テスト
     */
    public function test_component_state_sync(): void
    {
        $context = $this->testWithDebateContext(Header::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        // 初期状態のスナップショット
        $initialSnapshot = $this->snapshotComponentState($livewire, [
            'currentTurn',
            'currentTurnName',
            'nextTurnName',
            'currentSpeaker',
            'isMyTurn',
            'isPrepTime',
            'isAITurn'
        ]);

        // ターン進行
        $newTurnData = [
            'turn_number' => 1,
            'speaker' => 'negative',
            'is_prep_time' => false,
        ];

        $this->simulateRealtimeEvent(
            $livewire,
            "private:debate.{$debate->id}",
            'TurnAdvanced',
            $newTurnData
        );

        // 状態が変更されたことを確認
        $this->assertStateChanges($livewire, $initialSnapshot, [
            'currentTurn' => 1,
            'currentSpeaker' => 'negative',
            'isPrepTime' => false,
        ]);

        // 追加のアサーション
        $livewire->assertSet('currentTurn', 1)
            ->assertSet('currentSpeaker', 'negative')
            ->assertSet('isPrepTime', false);
    }

    /**
     * 複数のイベントシーケンステスト
     */
    public function test_multiple_event_sequence(): void
    {
        $context = $this->testWithDebateContext(Header::class);
        $livewire = $context['livewire'];
        $debate = $context['debate'];

        $eventSequence = [
            [
                'channel' => "private:debate.{$debate->id}",
                'event' => 'TurnAdvanced',
                'data' => [
                    'turn_number' => 1,
                    'speaker' => 'negative',
                    'is_prep_time' => false,
                ]
            ],
            [
                'channel' => "private:debate.{$debate->id}",
                'event' => 'TurnAdvanced',
                'data' => [
                    'turn_number' => 2,
                    'speaker' => 'affirmative',
                    'is_prep_time' => false,
                ]
            ]
        ];

        $this->dispatchRealtimeEvents($livewire, $eventSequence);

        // 最終的な状態を確認
        $livewire->assertSet('currentTurn', 2)
            ->assertSet('currentSpeaker', 'affirmative');
    }
}
