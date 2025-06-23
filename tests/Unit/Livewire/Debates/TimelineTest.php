<?php

namespace Tests\Unit\Livewire\Debates;

use Tests\Unit\Livewire\BaseLivewireTest;
use App\Livewire\Debates\Timeline;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use App\Services\DebateService;
use Livewire\Livewire;
use Tests\Helpers\MockHelpers;
use PHPUnit\Framework\Attributes\Test;

class TimelineTest extends BaseLivewireTest
{
    protected User $affirmativeUser;
    protected User $negativeUser;
    protected Room $room;
    protected Debate $debate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDebateData();
        MockHelpers::mockDebateConfigs();
        MockHelpers::mockAIConfigs();
    }

    private function setupDebateData(): void
    {
        $this->affirmativeUser = User::factory()->create();
        $this->negativeUser = User::factory()->create();

        $this->room = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'created_by' => $this->affirmativeUser->id,
        ]);

        $this->debate = Debate::factory()->create([
            'room_id' => $this->room->id,
            'affirmative_user_id' => $this->affirmativeUser->id,
            'negative_user_id' => $this->negativeUser->id,
            'current_turn' => 0,
        ]);
    }

    /**
     * TODO-051: Debates/Timeline基本機能テスト - コンポーネント初期化
     */
    #[Test]
    public function test_timeline_component_renders(): void
    {
        $mockFormat = [
            0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'duration' => 6 * 60, 'is_prep_time' => false],
            1 => ['speaker' => 'negative', 'name' => 'Response', 'duration' => 4 * 60, 'is_prep_time' => false],
            2 => ['speaker' => 'affirmative', 'name' => 'Rebuttal', 'duration' => 3 * 60, 'is_prep_time' => false],
        ];

        $this->mock(DebateService::class, function ($mock) use ($mockFormat) {
            $mock->shouldReceive('getFormat')
                ->andReturn($mockFormat);
        });

        Livewire::actingAs($this->affirmativeUser)
            ->test(Timeline::class, ['debate' => $this->debate])
            ->assertViewIs('livewire.debates.timeline')
            ->assertSet('debate.id', $this->debate->id)
            ->assertSet('currentTurn', 0)
            ->assertViewHas('format', $mockFormat)
            ->assertViewHas('currentTurn', 0);
    }

    /**
     * TODO-051: Debates/Timeline基本機能テスト - マウント初期化
     */
    #[Test]
    public function test_mount_initializes_timeline_correctly(): void
    {
        $mockFormat = [
            0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'duration' => 6 * 60, 'is_prep_time' => false],
            1 => ['speaker' => 'negative', 'name' => 'Response', 'duration' => 4 * 60, 'is_prep_time' => false],
            2 => ['speaker' => 'affirmative', 'name' => 'Rebuttal', 'duration' => 3 * 60, 'is_prep_time' => false],
        ];

        $this->mock(DebateService::class, function ($mock) use ($mockFormat) {
            $mock->shouldReceive('getFormat')
                ->andReturn($mockFormat);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Timeline::class, ['debate' => $this->debate]);

        // 基本プロパティの確認
        $livewire->assertSet('debate.id', $this->debate->id)
            ->assertSet('currentTurn', 0);

        // フォーマットが正しく設定されていることを確認
        $format = $livewire->get('format');
        $this->assertEquals($mockFormat, $format);
        $this->assertCount(3, $format);
        $this->assertEquals('Opening Statement', $format[0]['name']);
        $this->assertEquals('Response', $format[1]['name']);
        $this->assertEquals('Rebuttal', $format[2]['name']);
    }

    /**
     * TODO-051: Debates/Timeline基本機能テスト - 異なるターンでの初期化
     */
    #[Test]
    public function test_mount_with_different_current_turn(): void
    {
        // ディベートのターンを変更
        $this->debate->update(['current_turn' => 2]);

        $mockFormat = [
            0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'duration' => 6 * 60, 'is_prep_time' => false],
            1 => ['speaker' => 'negative', 'name' => 'Response', 'duration' => 4 * 60, 'is_prep_time' => false],
            2 => ['speaker' => 'affirmative', 'name' => 'Rebuttal', 'duration' => 3 * 60, 'is_prep_time' => false],
        ];

        $this->mock(DebateService::class, function ($mock) use ($mockFormat) {
            $mock->shouldReceive('getFormat')
                ->andReturn($mockFormat);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Timeline::class, ['debate' => $this->debate]);

        $livewire->assertSet('currentTurn', 2)
            ->assertViewHas('currentTurn', 2);
    }

    /**
     * TODO-051: Debates/Timelineイベント処理テスト - ターン進行イベント
     */
    #[Test]
    public function test_handle_turn_advanced_event(): void
    {
        $mockFormat = [
            0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'duration' => 6 * 60, 'is_prep_time' => false],
            1 => ['speaker' => 'negative', 'name' => 'Response', 'duration' => 4 * 60, 'is_prep_time' => false],
            2 => ['speaker' => 'affirmative', 'name' => 'Rebuttal', 'duration' => 3 * 60, 'is_prep_time' => false],
        ];

        $this->mock(DebateService::class, function ($mock) use ($mockFormat) {
            $mock->shouldReceive('getFormat')
                ->andReturn($mockFormat);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Timeline::class, ['debate' => $this->debate])
            ->assertSet('currentTurn', 0);

        // ターン進行イベントをシミュレート
        $eventData = ['current_turn' => 1];
        $livewire->dispatch('echo-presence:debate.' . $this->debate->id . ',TurnAdvanced', $eventData);

        $livewire->assertSet('currentTurn', 1)
            ->assertViewHas('currentTurn', 1);
    }

    /**
     * TODO-051: Debates/Timelineイベント処理テスト - 複数ターン進行
     */
    #[Test]
    public function test_handle_multiple_turn_advanced_events(): void
    {
        $mockFormat = [
            0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'duration' => 6 * 60, 'is_prep_time' => false],
            1 => ['speaker' => 'negative', 'name' => 'Response', 'duration' => 4 * 60, 'is_prep_time' => false],
            2 => ['speaker' => 'affirmative', 'name' => 'Rebuttal', 'duration' => 3 * 60, 'is_prep_time' => false],
            3 => ['speaker' => 'negative', 'name' => 'Final Statement', 'duration' => 5 * 60, 'is_prep_time' => false],
        ];

        $this->mock(DebateService::class, function ($mock) use ($mockFormat) {
            $mock->shouldReceive('getFormat')
                ->andReturn($mockFormat);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Timeline::class, ['debate' => $this->debate])
            ->assertSet('currentTurn', 0);

        // 複数のターン進行イベント
        $livewire->dispatch('echo-presence:debate.' . $this->debate->id . ',TurnAdvanced', ['current_turn' => 1])
            ->assertSet('currentTurn', 1);

        $livewire->dispatch('echo-presence:debate.' . $this->debate->id . ',TurnAdvanced', ['current_turn' => 2])
            ->assertSet('currentTurn', 2);

        $livewire->dispatch('echo-presence:debate.' . $this->debate->id . ',TurnAdvanced', ['current_turn' => 3])
            ->assertSet('currentTurn', 3);
    }

    /**
     * TODO-051: Debates/Timeline機能テスト - フォーマット取得
     */
    #[Test]
    public function test_get_filtered_turns_method(): void
    {
        $mockFormat = [
            0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'duration' => 6 * 60, 'is_prep_time' => false],
            1 => ['speaker' => 'negative', 'name' => 'Prep Time', 'duration' => 2 * 60, 'is_prep_time' => true],
            2 => ['speaker' => 'negative', 'name' => 'Response', 'duration' => 4 * 60, 'is_prep_time' => false],
            3 => ['speaker' => 'affirmative', 'name' => 'Rebuttal', 'duration' => 3 * 60, 'is_prep_time' => false],
        ];

        $this->mock(DebateService::class, function ($mock) use ($mockFormat) {
            $mock->shouldReceive('getFormat')
                ->andReturn($mockFormat);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Timeline::class, ['debate' => $this->debate]);

        // フォーマットが正しく取得されていることを確認
        $format = $livewire->get('format');
        $this->assertEquals($mockFormat, $format);
        $this->assertCount(4, $format);

        // 準備時間も含まれていることを確認
        $this->assertTrue($format[1]['is_prep_time']);
        $this->assertFalse($format[0]['is_prep_time']);
        $this->assertFalse($format[2]['is_prep_time']);
        $this->assertFalse($format[3]['is_prep_time']);
    }

    /**
     * TODO-051: Debates/Timeline機能テスト - 空のフォーマット処理
     */
    #[Test]
    public function test_handles_empty_format(): void
    {
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([]);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Timeline::class, ['debate' => $this->debate]);

        $format = $livewire->get('format');
        $this->assertEquals([], $format);
        $this->assertCount(0, $format);
    }

    /**
     * TODO-051: Debates/Timeline機能テスト - AIディベートフォーマット
     */
    #[Test]
    public function test_handles_ai_debate_format(): void
    {
        $aiUserId = (int)config('app.ai_user_id', 1);
        $aiUser = User::factory()->create(['id' => $aiUserId]);

        // 新しいRoomを作成してデータベース重複を回避
        $aiRoom = Room::factory()->create([
            'status' => Room::STATUS_DEBATING,
            'created_by' => $this->affirmativeUser->id,
        ]);

        $aiDebate = Debate::factory()->create([
            'room_id' => $aiRoom->id,
            'affirmative_user_id' => $this->affirmativeUser->id,
            'negative_user_id' => $aiUserId,
            'current_turn' => 0,
        ]);

        $mockFormat = [
            0 => ['speaker' => 'affirmative', 'name' => 'Human Opening', 'duration' => 6 * 60, 'is_prep_time' => false],
            1 => ['speaker' => 'negative', 'name' => 'AI Response', 'duration' => 4 * 60, 'is_prep_time' => false],
            2 => ['speaker' => 'affirmative', 'name' => 'Human Rebuttal', 'duration' => 3 * 60, 'is_prep_time' => false],
        ];

        $this->mock(DebateService::class, function ($mock) use ($mockFormat) {
            $mock->shouldReceive('getFormat')
                ->andReturn($mockFormat);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Timeline::class, ['debate' => $aiDebate]);

        $format = $livewire->get('format');
        $this->assertEquals($mockFormat, $format);
        $this->assertEquals('AI Response', $format[1]['name']);
    }

    /**
     * TODO-051: Debates/Timeline機能テスト - 無効なイベントデータ処理
     */
    #[Test]
    public function test_handles_invalid_event_data(): void
    {
        $mockFormat = [
            0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'duration' => 6 * 60, 'is_prep_time' => false],
        ];

        $this->mock(DebateService::class, function ($mock) use ($mockFormat) {
            $mock->shouldReceive('getFormat')
                ->andReturn($mockFormat);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Timeline::class, ['debate' => $this->debate])
            ->assertSet('currentTurn', 0);

        // 無効なイベントデータ（current_turnキーなし）
        $livewire->dispatch('echo-presence:debate.' . $this->debate->id . ',TurnAdvanced', ['invalid_key' => 1]);

        // currentTurnは変更されない（エラーが発生しないことを確認）
        $livewire->assertSet('currentTurn', 0);

        // 無効なイベントデータ（空の配列）
        $livewire->dispatch('echo-presence:debate.' . $this->debate->id . ',TurnAdvanced', []);
        $livewire->assertSet('currentTurn', 0);
    }

    /**
     * TODO-051: Debates/Timeline統合テスト - ビューレンダリング
     */
    #[Test]
    public function test_view_rendering(): void
    {
        $mockFormat = [
            0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'duration' => 6 * 60, 'is_prep_time' => false],
            1 => ['speaker' => 'negative', 'name' => 'Response', 'duration' => 4 * 60, 'is_prep_time' => false],
        ];

        $this->mock(DebateService::class, function ($mock) use ($mockFormat) {
            $mock->shouldReceive('getFormat')
                ->andReturn($mockFormat);
        });

        Livewire::actingAs($this->affirmativeUser)
            ->test(Timeline::class, ['debate' => $this->debate])
            ->assertViewIs('livewire.debates.timeline')
            ->assertViewHas('format', $mockFormat)
            ->assertViewHas('currentTurn', 0);
    }

    /**
     * TODO-051: Debates/Timeline統合テスト - 複雑なフォーマット処理
     */
    #[Test]
    public function test_complex_format_handling(): void
    {
        $complexFormat = [
            0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'is_prep_time' => false, 'duration' => 360],
            1 => ['speaker' => 'negative', 'name' => 'Prep Time', 'is_prep_time' => true, 'duration' => 300],
            2 => ['speaker' => 'negative', 'name' => 'Cross-Examination', 'is_prep_time' => false, 'duration' => 180],
            3 => ['speaker' => 'affirmative', 'name' => 'Prep Time', 'is_prep_time' => true, 'duration' => 300],
            4 => ['speaker' => 'affirmative', 'name' => 'Rebuttal', 'is_prep_time' => false, 'duration' => 240],
            5 => ['speaker' => 'negative', 'name' => 'Final Statement', 'is_prep_time' => false, 'duration' => 180],
        ];

        $this->mock(DebateService::class, function ($mock) use ($complexFormat) {
            $mock->shouldReceive('getFormat')
                ->andReturn($complexFormat);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Timeline::class, ['debate' => $this->debate]);

        $format = $livewire->get('format');
        $this->assertEquals($complexFormat, $format);
        $this->assertCount(6, $format);

        // 準備時間とディベート時間の混在を確認
        $this->assertFalse($format[0]['is_prep_time']);
        $this->assertTrue($format[1]['is_prep_time']);
        $this->assertFalse($format[2]['is_prep_time']);
        $this->assertTrue($format[3]['is_prep_time']);
        $this->assertFalse($format[4]['is_prep_time']);
        $this->assertFalse($format[5]['is_prep_time']);

        // 時間情報が含まれていることを確認
        $this->assertEquals(360, $format[0]['duration']);
        $this->assertEquals(300, $format[1]['duration']);
        $this->assertEquals(180, $format[2]['duration']);
    }

    /**
     * TODO-051: Debates/Timeline統合テスト - パフォーマンステスト
     */
    #[Test]
    public function test_performance_with_many_turns(): void
    {
        // 多数のターンを持つフォーマット
        $manyTurns = [];
        for ($i = 0; $i < 20; $i++) {
            $manyTurns[$i] = [
                'speaker' => $i % 2 === 0 ? 'affirmative' : 'negative',
                'name' => "Turn " . ($i + 1),
                'duration' => ($i % 4 === 1) ? 2 * 60 : 5 * 60, // 準備時間は2分、発言時間は5分
                'is_prep_time' => $i % 4 === 1, // 4回に1回は準備時間
            ];
        }

        $this->mock(DebateService::class, function ($mock) use ($manyTurns) {
            $mock->shouldReceive('getFormat')
                ->andReturn($manyTurns);
        });

        $start = microtime(true);

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Timeline::class, ['debate' => $this->debate]);

        // 複数のターン進行イベントを処理
        for ($i = 0; $i < 10; $i++) {
            $livewire->dispatch('echo-presence:debate.' . $this->debate->id . ',TurnAdvanced', ['current_turn' => $i]);
        }

        $duration = microtime(true) - $start;

        // パフォーマンステスト（1秒以内で完了）
        $this->assertLessThan(1.0, $duration, 'Timeline component performance test failed');

        // 最終状態の確認
        $livewire->assertSet('currentTurn', 9);
        $format = $livewire->get('format');
        $this->assertCount(20, $format);
    }
}
