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
}
