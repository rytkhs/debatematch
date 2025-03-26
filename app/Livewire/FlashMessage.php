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
