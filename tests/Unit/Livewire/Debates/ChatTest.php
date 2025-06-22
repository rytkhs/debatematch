<?php

namespace Tests\Unit\Livewire\Debates;

use PHPUnit\Framework\Attributes\Test;
use App\Livewire\Debates\Chat;
use App\Models\Debate;
use App\Models\DebateMessage;
use App\Models\Room;
use App\Models\User;
use App\Services\DebateService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Livewire;
use Tests\Unit\Livewire\BaseLivewireTest;

/**
 * Chatコンポーネントのテスト
 *
 * TODO-041: Chat基本機能テスト
 */
class ChatTest extends BaseLivewireTest
{
    private Debate $debate;
    private User $affirmativeUser;
    private User $negativeUser;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用のディベートデータを作成
        $this->setupDebateData();
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

        // ルームにユーザーを参加させる
        $this->room->users()->attach($this->affirmativeUser->id, ['side' => 'affirmative']);
        $this->room->users()->attach($this->negativeUser->id, ['side' => 'negative']);
    }

    /**
     * TODO-041: 基本機能テスト - 初期化テスト
     */
    #[Test]
    public function test_mount_initializes_component_correctly(): void
    {
        // DebateServiceをモック
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'is_prep_time' => false, 'is_questions' => false]
                ]);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Chat::class, ['debate' => $this->debate]);

        $livewire
            ->assertSet('debate.id', $this->debate->id)
            ->assertSet('activeTab', 'all');

        // Check that filteredMessages is a Collection instance
        $this->assertInstanceOf(Collection::class, $livewire->get('filteredMessages'));
    }

    /**
     * TODO-041: 基本機能テスト - メッセージ読み込み
     */
    #[Test]
    public function test_load_messages_retrieves_debate_messages(): void
    {
        // テスト用メッセージを作成
        DebateMessage::factory()->count(3)->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->affirmativeUser->id,
            'turn' => 0,
        ]);

        DebateMessage::factory()->count(2)->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->negativeUser->id,
            'turn' => 1,
        ]);

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Chat::class, ['debate' => $this->debate]);

        $filteredMessages = $livewire->get('filteredMessages');
        $this->assertCount(5, $filteredMessages);
    }

    /**
     * TODO-041: 基本機能テスト - タブフィルター機能
     */
    #[Test]
    public function test_filter_messages_by_tab_all(): void
    {
        // 異なるターンのメッセージを作成
        DebateMessage::factory()->count(2)->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->affirmativeUser->id,
            'turn' => 0,
        ]);

        DebateMessage::factory()->count(3)->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->negativeUser->id,
            'turn' => 1,
        ]);

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Chat::class, ['debate' => $this->debate])
            ->set('activeTab', 'all');

        $filteredMessages = $livewire->get('filteredMessages');
        $this->assertCount(5, $filteredMessages); // 全メッセージが表示される
    }

    /**
     * TODO-041: 基本機能テスト - 特定ターンのフィルター
     */
    #[Test]
    public function test_filter_messages_by_specific_turn(): void
    {
        // 異なるターンのメッセージを作成
        DebateMessage::factory()->count(2)->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->affirmativeUser->id,
            'turn' => 0,
        ]);

        DebateMessage::factory()->count(3)->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->negativeUser->id,
            'turn' => 1,
        ]);

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Chat::class, ['debate' => $this->debate])
            ->set('activeTab', '0'); // ターン0でフィルター

        $filteredMessages = $livewire->get('filteredMessages');
        $this->assertCount(2, $filteredMessages); // ターン0のメッセージのみ

        // 全メッセージのターンが0であることを確認
        foreach ($filteredMessages as $message) {
            $this->assertEquals(0, $message->turn);
        }
    }

    /**
     * TODO-041: 基本機能テスト - タブ更新処理
     */
    #[Test]
    public function test_updated_active_tab_reloads_messages(): void
    {
        // テスト用メッセージを作成
        DebateMessage::factory()->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->affirmativeUser->id,
            'turn' => 0,
        ]);

        DebateMessage::factory()->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->negativeUser->id,
            'turn' => 1,
        ]);

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Chat::class, ['debate' => $this->debate]);

        // 初期状態（全メッセージ）
        $this->assertCount(2, $livewire->get('filteredMessages'));

        // ターン0でフィルター
        $livewire->set('activeTab', '0');
        $this->assertCount(1, $livewire->get('filteredMessages'));

        // 再び全メッセージに戻す
        $livewire->set('activeTab', 'all');
        $this->assertCount(2, $livewire->get('filteredMessages'));
    }

    /**
     * TODO-041: 基本機能テスト - リアルタイムメッセージ受信処理
     */
    #[Test]
    public function test_handle_message_received_refreshes_messages(): void
    {
        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Chat::class, ['debate' => $this->debate]);

        // 初期状態の確認
        $this->assertCount(0, $livewire->get('filteredMessages'));

        // 新しいメッセージを作成
        DebateMessage::factory()->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->affirmativeUser->id,
            'turn' => 0,
        ]);

        // メッセージ受信イベントをシミュレート
        $livewire->dispatch('echo-private:debate.' . $this->debate->id . ',DebateMessageSent');

        // メッセージが読み込まれることを確認
        $this->assertCount(1, $livewire->get('filteredMessages'));

        // フラッシュメッセージとイベントの発火を確認
        $livewire
            ->assertDispatched('message-received')
            ->assertDispatched('showFlashMessage');
    }

    /**
     * TODO-041: 基本機能テスト - メッセージ送信後の更新
     */
    #[Test]
    public function test_refresh_messages_on_message_sent(): void
    {
        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Chat::class, ['debate' => $this->debate]);

        // 初期状態の確認
        $this->assertCount(0, $livewire->get('filteredMessages'));

        // 新しいメッセージを作成
        DebateMessage::factory()->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->affirmativeUser->id,
            'turn' => 0,
        ]);

        // message-sentイベントをシミュレート
        $livewire->dispatch('message-sent');

        // メッセージが読み込まれることを確認
        $this->assertCount(1, $livewire->get('filteredMessages'));
    }

    /**
     * TODO-041: 基本機能テスト - フィルタードターンズ取得
     */
    #[Test]
    public function test_get_filtered_turns_property_excludes_prep_time(): void
    {
        // DebateServiceをモックして準備時間ターンを含むフォーマットを返す
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'is_prep_time' => false],
                    1 => ['speaker' => 'negative', 'name' => 'Prep Time', 'is_prep_time' => true],
                    2 => ['speaker' => 'negative', 'name' => 'Response', 'is_prep_time' => false],
                ]);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Chat::class, ['debate' => $this->debate]);

        $filteredTurns = $livewire->get('filteredTurns');

        // 準備時間のターンが除外されていることを確認
        $this->assertCount(2, $filteredTurns);
        $this->assertEquals('Opening Statement', $filteredTurns[0]['name']);
        $this->assertEquals('Response', $filteredTurns[2]['name']);
        $this->assertArrayNotHasKey(1, $filteredTurns); // 準備時間のターンは除外される
    }

    /**
     * TODO-041: 基本機能テスト - メッセージ順序テスト
     */
    #[Test]
    public function test_messages_are_ordered_by_created_at(): void
    {
        // 異なる作成時刻でメッセージを作成
        $firstMessage = DebateMessage::factory()->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->affirmativeUser->id,
            'turn' => 0,
            'created_at' => now()->subMinutes(2),
        ]);

        $secondMessage = DebateMessage::factory()->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->negativeUser->id,
            'turn' => 1,
            'created_at' => now()->subMinute(),
        ]);

        $thirdMessage = DebateMessage::factory()->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->affirmativeUser->id,
            'turn' => 2,
            'created_at' => now(),
        ]);

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Chat::class, ['debate' => $this->debate]);

        $filteredMessages = $livewire->get('filteredMessages');

        // 作成時刻順に並んでいることを確認
        $this->assertEquals($firstMessage->id, $filteredMessages[0]->id);
        $this->assertEquals($secondMessage->id, $filteredMessages[1]->id);
        $this->assertEquals($thirdMessage->id, $filteredMessages[2]->id);
    }

    /**
     * TODO-041: 基本機能テスト - ユーザー関連付けテスト
     */
    #[Test]
    public function test_messages_include_user_relationship(): void
    {
        DebateMessage::factory()->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->affirmativeUser->id,
            'turn' => 0,
        ]);

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Chat::class, ['debate' => $this->debate]);

        $filteredMessages = $livewire->get('filteredMessages');
        $message = $filteredMessages->first();

        // ユーザー関係が読み込まれていることを確認
        $this->assertNotNull($message->user);
        $this->assertEquals($this->affirmativeUser->id, $message->user->id);
    }

    /**
     * TODO-041: 基本機能テスト - 空のメッセージ処理
     */
    #[Test]
    public function test_handles_empty_messages_gracefully(): void
    {
        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Chat::class, ['debate' => $this->debate]);

        $filteredMessages = $livewire->get('filteredMessages');

        // 空のコレクションが返されることを確認
        $this->assertInstanceOf(Collection::class, $filteredMessages);
        $this->assertCount(0, $filteredMessages);
    }

    /**
     * TODO-041: 基本機能テスト - 異なるディベートのメッセージ除外
     */
    #[Test]
    public function test_filters_out_messages_from_other_debates(): void
    {
        // 別のディベートを作成
        $otherDebate = Debate::factory()->create();

        // 現在のディベートのメッセージ
        DebateMessage::factory()->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->affirmativeUser->id,
            'turn' => 0,
        ]);

        // 別のディベートのメッセージ
        DebateMessage::factory()->create([
            'debate_id' => $otherDebate->id,
            'user_id' => $this->affirmativeUser->id,
            'turn' => 0,
        ]);

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Chat::class, ['debate' => $this->debate]);

        $filteredMessages = $livewire->get('filteredMessages');

        // 現在のディベートのメッセージのみ表示されることを確認
        $this->assertCount(1, $filteredMessages);
        $this->assertEquals($this->debate->id, $filteredMessages->first()->debate_id);
    }

    /**
     * TODO-041: 基本機能テスト - パフォーマンステスト
     */
    #[Test]
    public function test_component_performance_with_many_messages(): void
    {
        // 多数のメッセージを作成（turnカラムも設定）
        DebateMessage::factory()->count(30)->create([
            'debate_id' => $this->debate->id,
            'user_id' => $this->affirmativeUser->id,
            'turn' => 0, // turnカラムを明示的に設定
        ]);

        $start = microtime(true);

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(Chat::class, ['debate' => $this->debate]);

        // フィルター操作を複数回実行
        for ($i = 0; $i < 5; $i++) {
            $livewire->set('activeTab', 'all');
            $livewire->set('activeTab', '0');
        }

        $duration = microtime(true) - $start;

        // パフォーマンステスト（1.5秒以内で完了、最適化: 制限時間短縮）
        $this->assertLessThan(1.5, $duration, 'Chat component performance test failed');

        // メッセージが正しく読み込まれていることを確認
        $this->assertGreaterThan(0, count($livewire->get('filteredMessages')));
    }
}
