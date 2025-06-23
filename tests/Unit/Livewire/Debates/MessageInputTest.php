<?php

namespace Tests\Unit\Livewire\Debates;

use PHPUnit\Framework\Attributes\Test;
use App\Livewire\Debates\MessageInput;
use App\Models\Debate;
use App\Models\DebateMessage;
use App\Models\Room;
use App\Models\User;
use App\Services\DebateService;
use App\Jobs\GenerateAIResponseJob;
use App\Events\DebateMessageSent;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\Unit\Livewire\BaseLivewireTest;

/**
 * MessageInputコンポーネントのテスト
 *
 * TODO-039: 基本機能テスト
 * TODO-040: 権限・状態テスト
 */
class MessageInputTest extends BaseLivewireTest
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
     * TODO-039: 基本機能テスト - 初期化テスト
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
            ->test(MessageInput::class, ['debate' => $this->debate]);

        $livewire
            ->assertSet('debate.id', $this->debate->id)
            ->assertSet('newMessage', '')
            ->assertSet('currentSpeaker', 'affirmative') // 最初のターンは肯定側
            ->assertSet('isPrepTime', false)
            ->assertSet('isQuestioningTurn', false);
    }

    /**
     * TODO-039: 基本機能テスト - メッセージ送信
     */
    #[Test]
    public function test_send_message_creates_debate_message(): void
    {
        // DebateServiceをモック
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'is_prep_time' => false, 'is_questions' => false]
                ]);
        });

        $this->assertEquals(0, DebateMessage::count());

        Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate])
            ->set('newMessage', 'This is my argument.')
            ->call('sendMessage');

        $this->assertEquals(1, DebateMessage::count());

        $message = DebateMessage::first();
        $this->assertEquals('This is my argument.', $message->message);
        $this->assertEquals($this->affirmativeUser->id, $message->user_id);
        $this->assertEquals($this->debate->id, $message->debate_id);
        $this->assertEquals(0, $message->turn);
    }

    /**
     * TODO-039: 基本機能テスト - メッセージ送信後の状態リセット
     */
    #[Test]
    public function test_send_message_clears_input_field(): void
    {
        // DebateServiceをモック
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'is_prep_time' => false, 'is_questions' => false]
                ]);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate])
            ->set('newMessage', 'Test message')
            ->call('sendMessage');

        $livewire->assertSet('newMessage', '');
    }

    /**
     * TODO-039: 基本機能テスト - バリデーション
     */
    #[Test]
    public function test_message_validation_required(): void
    {
        // DebateServiceをモック
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'is_prep_time' => false, 'is_questions' => false]
                ]);
        });

        Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate])
            ->set('newMessage', '')
            ->call('sendMessage')
            ->assertHasErrors('newMessage');
    }

    /**
     * TODO-039: 基本機能テスト - 文字数制限バリデーション
     */
    #[Test]
    public function test_message_validation_max_length(): void
    {
        // DebateServiceをモック
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'is_prep_time' => false, 'is_questions' => false]
                ]);
        });

        $longMessage = str_repeat('a', 5001); // 5000文字を超過

        Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate])
            ->set('newMessage', $longMessage)
            ->call('sendMessage')
            ->assertHasErrors(['newMessage' => ['max']]);
    }

    /**
     * TODO-040: 権限・状態テスト - 発言権限のチェック
     */
    #[Test]
    public function test_can_send_message_only_when_users_turn(): void
    {
        // DebateServiceをモック
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'is_prep_time' => false, 'is_questions' => false]
                ]);
        });

        // 肯定側のターン（current_turn = 0）で肯定側ユーザーがメッセージ送信
        Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate])
            ->set('newMessage', 'Affirmative message')
            ->call('sendMessage');

        $this->assertEquals(1, DebateMessage::count());

        // 同じターンで否定側ユーザーが送信しようとしても無効
        Livewire::actingAs($this->negativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate])
            ->set('newMessage', 'Negative message should not be sent')
            ->call('sendMessage');

        // メッセージ数は増えない
        $this->assertEquals(1, DebateMessage::count());
    }

    /**
     * TODO-040: 権限・状態テスト - 準備時間中は送信不可
     */
    #[Test]
    public function test_cannot_send_message_during_prep_time(): void
    {
        // 準備時間のターンをモック
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Prep Time', 'is_prep_time' => true]
                ]);
        });

        Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate])
            ->set('newMessage', 'Message during prep time')
            ->call('sendMessage');

        // メッセージは作成されない
        $this->assertEquals(0, DebateMessage::count());
    }

    /**
     * TODO-040: 権限・状態テスト - ルーム状態チェック
     */
    #[Test]
    public function test_cannot_send_message_when_room_not_debating(): void
    {
        // ルームを待機状態に変更
        $this->room->update(['status' => Room::STATUS_WAITING]);
        $this->debate->room()->associate($this->room);

        Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate])
            ->set('newMessage', 'Message when not debating')
            ->call('sendMessage');

        // メッセージは作成されない
        $this->assertEquals(0, DebateMessage::count());
    }

    /**
     * TODO-040: 権限・状態テスト - 質疑応答ターンでの権限
     */
    #[Test]
    public function test_can_send_message_during_questioning_turn(): void
    {
        // 質疑応答ターンをモック
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Questions', 'is_questions' => true]
                ]);
        });

        // 否定側ユーザーでも質疑応答時は送信可能
        Livewire::actingAs($this->negativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate])
            ->set('newMessage', 'Question from negative side')
            ->call('sendMessage');

        $this->assertEquals(1, DebateMessage::count());
    }

    /**
     * TODO-040: 権限・状態テスト - AI応答トリガーテスト
     */
    #[Test]
    public function test_triggers_ai_response_in_ai_debate_questioning_turn(): void
    {
        Queue::fake();

        // AIディベートに設定
        $this->room->update(['is_ai_debate' => true]);

        // 質疑応答ターンをモック
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Questions', 'is_questions' => true]
                ]);
        });

        Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate])
            ->set('newMessage', 'Question for AI')
            ->call('sendMessage');

        // AIレスポンスジョブがディスパッチされることを確認
        Queue::assertPushed(GenerateAIResponseJob::class);
    }

    /**
     * TODO-040: 権限・状態テスト - イベント発火テスト
     */
    #[Test]
    public function test_dispatches_events_on_message_send(): void
    {
        Event::fake();

        // DebateServiceをモック
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'is_prep_time' => false, 'is_questions' => false]
                ]);
        });

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate])
            ->set('newMessage', 'Test message for events')
            ->call('sendMessage');

        // Livewireイベントの発火を確認
        $livewire
            ->assertDispatched('message-sent')
            ->assertDispatched('showFlashMessage');

        // Laravelイベントの発火を確認
        Event::assertDispatched(DebateMessageSent::class);
    }

    /**
     * TODO-040: 権限・状態テスト - ターン状態同期テスト
     */
    #[Test]
    public function test_sync_turn_state_updates_properties_correctly(): void
    {
        // ターン2（否定側）に変更
        $this->debate->update(['current_turn' => 1]);

        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    1 => ['speaker' => 'negative', 'name' => 'Negative Opening', 'is_prep_time' => false]
                ]);
        });

        $livewire = Livewire::actingAs($this->negativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate]);

        $livewire
            ->assertSet('currentSpeaker', 'negative')
            ->assertSet('isMyTurn', true)
            ->assertSet('isPrepTime', false);
    }

    /**
     * TODO-040: 権限・状態テスト - リアルタイムイベント処理
     */
    #[Test]
    public function test_handles_turn_advanced_event(): void
    {
        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate]);

        // ターンが進んだイベントをシミュレート
        $this->debate->update(['current_turn' => 1]);

        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    1 => ['speaker' => 'negative', 'name' => 'Negative Opening', 'is_prep_time' => false]
                ]);
        });

        $livewire->dispatch('echo-presence:debate.' . $this->debate->id . ',TurnAdvanced');

        $livewire
            ->assertSet('currentSpeaker', 'negative')
            ->assertSet('isMyTurn', false); // 肯定側ユーザーなので自分のターンではない
    }

    /**
     * TODO-040: 権限・状態テスト - ディベート開始イベント処理
     */
    #[Test]
    public function test_handles_debate_started_event(): void
    {
        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate])
            ->set('newMessage', 'Some text');

        $livewire->dispatch('echo-presence:debate.' . $this->debate->id . ',DebateStarted');

        $livewire->assertSet('newMessage', '');
    }

    /**
     * TODO-040: 権限・状態テスト - ユーザー権限判定テスト
     */
    #[Test]
    public function test_check_if_users_turn_logic(): void
    {
        // DebateServiceをモック
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'is_prep_time' => false, 'is_questions' => false]
                ]);
        });

        // 肯定側ターンで肯定側ユーザー
        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate]);
        $livewire->assertSet('isMyTurn', true);

        // 肯定側ターンで否定側ユーザー
        $livewire = Livewire::actingAs($this->negativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate]);
        $livewire->assertSet('isMyTurn', false);
    }

    /**
     * TODO-040: 権限・状態テスト - ターン境界値テスト
     */
    #[Test]
    public function test_turn_boundaries(): void
    {
        // 最終ターンでもメッセージ送信可能
        $finalTurn = 10;
        $this->debate->update(['current_turn' => $finalTurn]);

        $this->mock(DebateService::class, function ($mock) use ($finalTurn) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    $finalTurn => ['speaker' => 'affirmative', 'name' => 'Final Statement', 'is_prep_time' => false]
                ]);
        });

        Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate])
            ->set('newMessage', 'Final message')
            ->call('sendMessage');

        $this->assertEquals(1, DebateMessage::count());
    }

    /**
     * TODO-040: 権限・状態テスト - パフォーマンステスト
     */
    #[Test]
    public function test_component_performance_with_multiple_operations(): void
    {
        // DebateServiceをモック
        $this->mock(DebateService::class, function ($mock) {
            $mock->shouldReceive('getFormat')
                ->andReturn([
                    0 => ['speaker' => 'affirmative', 'name' => 'Opening Statement', 'is_prep_time' => false, 'is_questions' => false]
                ]);
        });

        $start = microtime(true);

        $livewire = Livewire::actingAs($this->affirmativeUser)
            ->test(MessageInput::class, ['debate' => $this->debate]);

        // 複数の操作を実行
        for ($i = 0; $i < 5; $i++) {
            $livewire
                ->set('newMessage', "Message $i")
                ->call('sendMessage')
                ->dispatch('echo-presence:debate.' . $this->debate->id . ',TurnAdvanced');
        }

        $duration = microtime(true) - $start;

        // 1秒以内で完了することを確認
        $this->assertLessThan(1.0, $duration, 'Component performance test failed');
        $this->assertEquals(5, DebateMessage::count());
    }
}
