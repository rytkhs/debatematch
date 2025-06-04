<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class FlashMessage extends Component
{
    public $message = '';
    public $type = '';
    public $show = false;

    #[On('showFlashMessage')]
    public function showFlashMessage($message, $type = 'success')
    {
        $this->message = $message;
        $this->type = $type;
        $this->show = true;

        $this->dispatch('start-flash-message-timeout');
    }

    #[On('showDelayedFlashMessage')]
    public function showDelayedFlashMessage($message, $type = 'success', $delay = 1000)
    {
        // JavaScriptに遅延表示を依頼
        $this->dispatch('start-delayed-flash-message', [
            'message' => $message,
            'type' => $type,
            'delay' => $delay
        ]);
    }

    public function hideFlashMessage()
    {
        $this->show = false;
        $this->message = '';
        $this->type = '';
    }

    public function render()
    {
        return view('livewire.flash-message');
    }
}
