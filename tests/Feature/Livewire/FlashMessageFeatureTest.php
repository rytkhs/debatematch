<?php

namespace Tests\Feature\Livewire;

use App\Livewire\FlashMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlashMessageFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function flash_message_component_renders_successfully()
    {
        Livewire::test(FlashMessage::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.flash-message');
    }

    #[Test]
    public function flash_message_can_be_shown()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $livewire->call('showFlashMessage', 'Test message', 'success')
            ->assertSet('message', 'Test message')
            ->assertSet('type', 'success')
            ->assertSet('show', true);
    }

    #[Test]
    public function flash_message_can_be_hidden()
    {
        $livewire = Livewire::test(FlashMessage::class)
            ->set('show', true)
            ->set('message', 'Test message');

        $livewire->call('hideFlashMessage')
            ->assertSet('show', false);
    }

    #[Test]
    public function flash_message_supports_different_types()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $types = ['success', 'error', 'warning', 'info'];

        foreach ($types as $type) {
            $livewire->call('showFlashMessage', "Test {$type} message", $type)
                ->assertSet('type', $type)
                ->assertSet('message', "Test {$type} message");
        }
    }

    #[Test]
    public function flash_message_can_be_shown_with_delay()
    {
        $livewire = Livewire::test(FlashMessage::class);

        // showDelayedFlashMessageはJavaScriptイベントを発行するだけなので、
        // メッセージの状態は変わらない
        $livewire->call('showDelayedFlashMessage', 'Delayed message', 'info', 2000)
            ->assertSet('message', '')
            ->assertSet('type', '')
            ->assertSet('show', false);
    }

    #[Test]
    public function flash_message_handles_empty_message()
    {
        $livewire = Livewire::test(FlashMessage::class);

        $livewire->call('showFlashMessage', '', 'success')
            ->assertSet('message', '')
            ->assertSet('type', 'success')
            ->assertSet('show', true);
    }

    #[Test]
    public function flash_message_handles_long_message()
    {
        $livewire = Livewire::test(FlashMessage::class);
        $longMessage = str_repeat('This is a very long message. ', 50);

        $livewire->call('showFlashMessage', $longMessage, 'info')
            ->assertSet('message', $longMessage)
            ->assertSet('type', 'info')
            ->assertSet('show', true);
    }

    #[Test]
    public function flash_message_handles_special_characters()
    {
        $livewire = Livewire::test(FlashMessage::class);
        $specialMessage = 'Message with special chars: <>&"\'';

        $livewire->call('showFlashMessage', $specialMessage, 'warning')
            ->assertSet('message', $specialMessage)
            ->assertSet('type', 'warning')
            ->assertSet('show', true);
    }

    #[Test]
    public function flash_message_state_persistence()
    {
        $livewire = Livewire::test(FlashMessage::class);

        // Show message
        $livewire->call('showFlashMessage', 'Persistent message', 'success')
            ->assertSet('show', true);

        // Hide message
        $livewire->call('hideFlashMessage')
            ->assertSet('show', false);

        // Message content should be cleared
        $livewire->assertSet('message', '')
            ->assertSet('type', '');
    }
}
