<?php

namespace Tests\Unit\Livewire;

use PHPUnit\Framework\Attributes\Test;
use App\Livewire\FlashMessage;
use Livewire\Livewire;
use Tests\Unit\Livewire\BaseLivewireTest;

class FlashMessageTest extends BaseLivewireTest
{
    #[Test]
    public function testMount()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $livewire
            ->assertSet('message', '')
            ->assertSet('type', '')
            ->assertSet('show', false);
    }

    #[Test]
    public function testRender()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $livewire
            ->assertStatus(200)
            ->assertViewIs('livewire.flash-message');
    }

    #[Test]
    public function testShowFlashMessage()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $livewire
            ->dispatch('showFlashMessage', 'Test message', 'success')
            ->assertSet('message', 'Test message')
            ->assertSet('type', 'success')
            ->assertSet('show', true);
    }

    #[Test]
    public function testShowFlashMessageWithDefaultType()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $livewire
            ->dispatch('showFlashMessage', 'Default type message')
            ->assertSet('message', 'Default type message')
            ->assertSet('type', 'success')
            ->assertSet('show', true);
    }

    #[Test]
    public function testShowFlashMessageWithDifferentTypes()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $types = ['success', 'error', 'warning', 'info'];

        foreach ($types as $type) {
            $livewire
                ->dispatch('showFlashMessage', "Test {$type} message", $type)
                ->assertSet('message', "Test {$type} message")
                ->assertSet('type', $type)
                ->assertSet('show', true);
        }
    }

    #[Test]
    public function testShowDelayedFlashMessage()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $livewire
            ->dispatch('showDelayedFlashMessage', 'Delayed message', 'warning', 3000)
            ->assertDispatched('start-delayed-flash-message', function ($event, $params) {
                return isset($params[0]) &&
                    $params[0]['message'] === 'Delayed message' &&
                    $params[0]['type'] === 'warning' &&
                    $params[0]['delay'] === 3000;
            });
    }

    #[Test]
    public function testShowDelayedFlashMessageWithDefaultValues()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $livewire
            ->dispatch('showDelayedFlashMessage', 'Default delayed message')
            ->assertDispatched('start-delayed-flash-message', function ($event, $params) {
                return isset($params[0]) &&
                    $params[0]['message'] === 'Default delayed message' &&
                    $params[0]['type'] === 'success' &&
                    $params[0]['delay'] === 1000;
            });
    }

    #[Test]
    public function testHideFlashMessage()
    {
        $livewire = Livewire::test(FlashMessage::class);

        // メッセージを表示してから非表示にする
        $livewire
            ->dispatch('showFlashMessage', 'Message to hide', 'error')
            ->assertSet('show', true)
            ->call('hideFlashMessage')
            ->assertSet('show', false)
            ->assertSet('message', '')
            ->assertSet('type', '');
    }

    #[Test]
    public function testHideFlashMessageWhenNotShown()
    {
        $livewire = Livewire::test(FlashMessage::class);

        // 非表示状態で hideFlashMessage() を呼んでもエラーにならない
        $livewire
            ->call('hideFlashMessage')
            ->assertSet('show', false);
    }

    #[Test]
    public function testMultipleMessageUpdates()
    {
        $livewire = Livewire::test(FlashMessage::class);

        // 最初のメッセージ
        $livewire
            ->dispatch('showFlashMessage', 'First message', 'success')
            ->assertSet('message', 'First message')
            ->assertSet('type', 'success')
            ->assertSet('show', true);

        // 二番目のメッセージ
        $livewire
            ->dispatch('showFlashMessage', 'Second message', 'error')
            ->assertSet('message', 'Second message')
            ->assertSet('type', 'error')
            ->assertSet('show', true);
    }

    #[Test]
    public function testEmptyMessage()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $livewire
            ->dispatch('showFlashMessage', '', 'info')
            ->assertSet('message', '')
            ->assertSet('type', 'info')
            ->assertSet('show', true);
    }

    #[Test]
    public function testLongMessage()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $longMessage = str_repeat('This is a very long message. ', 50);

        $livewire
            ->dispatch('showFlashMessage', $longMessage, 'warning')
            ->assertSet('message', $longMessage)
            ->assertSet('type', 'warning')
            ->assertSet('show', true);
    }

    #[Test]
    public function testMessageWithSpecialCharacters()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $specialMessage = 'Special chars: <script>alert("test")</script> & symbols ñáéíóú';

        $livewire
            ->dispatch('showFlashMessage', $specialMessage, 'error')
            ->assertSet('message', $specialMessage)
            ->assertSet('type', 'error')
            ->assertSet('show', true);
    }

    #[Test]
    public function testInvalidMessageType()
    {
        $livewire = Livewire::test(FlashMessage::class);

        // 無効なタイプでもエラーにならない
        $livewire
            ->dispatch('showFlashMessage', 'Invalid type message', 'invalid')
            ->assertSet('message', 'Invalid type message')
            ->assertSet('type', 'invalid')
            ->assertSet('show', true);
    }

    #[Test]
    public function testNullMessageType()
    {
        $livewire = Livewire::test(FlashMessage::class);

        // null を渡すとデフォルトの 'success' になる
        $livewire
            ->dispatch('showFlashMessage', 'Null type message', null)
            ->assertSet('message', 'Null type message')
            ->assertSet('type', null)
            ->assertSet('show', true);
    }

    #[Test]
    public function testMessageVisibilityToggle()
    {
        $livewire = Livewire::test(FlashMessage::class);

        // 表示 -> 非表示 -> 表示のサイクル
        $livewire
            ->dispatch('showFlashMessage', 'Toggle message', 'success')
            ->assertSet('show', true)
            ->call('hideFlashMessage')
            ->assertSet('show', false)
            ->dispatch('showFlashMessage', 'New toggle message', 'error')
            ->assertSet('show', true)
            ->assertSet('message', 'New toggle message')
            ->assertSet('type', 'error');
    }

    #[Test]
    public function testEventDispatchOnShowMessage()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $livewire
            ->dispatch('showFlashMessage', 'Event test message', 'success')
            ->assertDispatched('start-flash-message-timeout');
    }

    #[Test]
    public function testJavaScriptEventDispatch()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $livewire
            ->dispatch('showDelayedFlashMessage', 'JS event message', 'warning', 2000)
            ->assertDispatched('start-delayed-flash-message');
    }

    #[Test]
    public function testComponentStateAfterMultipleOperations()
    {
        $livewire = Livewire::test(FlashMessage::class);

        // 複数の操作後の状態確認
        $livewire
            ->dispatch('showFlashMessage', 'First', 'success')
            ->call('hideFlashMessage')
            ->dispatch('showDelayedFlashMessage', 'Delayed', 'error', 1000)
            ->dispatch('showFlashMessage', 'Final', 'info')
            ->assertSet('message', 'Final')
            ->assertSet('type', 'info')
            ->assertSet('show', true);
    }

    #[Test]
    public function testMessagePersistenceAcrossEvents()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $livewire
            ->dispatch('showFlashMessage', 'Persistent message', 'warning')
            ->assertSet('message', 'Persistent message')
            ->refresh() // コンポーネントを再描画
            ->assertSet('message', 'Persistent message')
            ->assertSet('type', 'warning')
            ->assertSet('show', true);
    }

    #[Test]
    public function testComponentRenderWithDifferentStates()
    {
        // 非表示状態でのレンダリング
        $livewire = Livewire::test(FlashMessage::class);
        $livewire->assertStatus(200);

        // 表示状態でのレンダリング
        $livewire
            ->dispatch('showFlashMessage', 'Rendered message', 'success')
            ->assertStatus(200)
            ->assertSee('Rendered message');

        // 遅延表示のイベント送信
        $livewire
            ->dispatch('showDelayedFlashMessage', 'Delayed rendered message', 'error', 2000)
            ->assertStatus(200);
    }
}
