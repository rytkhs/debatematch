<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Debate;
use App\Models\DebateMessage;
use Illuminate\Support\Facades\Auth;
use App\Events\DebateMessageSent;
use App\Events\TurnAdvanced;

class DebateChat extends Component
{
    public $debate;
    public $message;
    public $current_turn;
    public $current_speaker;
    public $isCurrentSpeaker = false;



    protected $rules = [
        'message' => 'required|string|max:1000',
    ];

    protected $listeners = [
        'messageReceived' => 'receiveMessage',
    ];

    public function getListeners()
    {
        return [
            "echo:debate.{$this->debate->room_id},TurnAdvanced" => 'updateTurn',
        ];
    }
    public function mount(Debate $debate)
    {
        $this->debate = $debate;
        $this->current_turn = $debate->current_turn;
        $this->current_speaker = Debate::$turns[$this->current_turn]['speaker'];
        $this->checkIfCurrentSpeaker();
    }


    public function updateTurn($event)
    {
        $this->current_turn = $event['current_turn'];
        $this->current_speaker = $event['speaker'];
        $this->checkIfCurrentSpeaker();
    }

    private function checkIfCurrentSpeaker()
    {
        if ($this->current_speaker === 'affirmative') {
            $this->isCurrentSpeaker = ($this->debate->affirmative_user_id === Auth::id());
        } elseif ($this->current_speaker === 'negative') {
            $this->isCurrentSpeaker = ($this->debate->negative_user_id === Auth::id());
        } else {
            $this->isCurrentSpeaker = false;
        }
    }

    public function sendMessage()
    {
        if (!$this->isCurrentSpeaker) {
            session()->flash('error', 'あなたは現在のターンを進める権限がありません。');
            return;
        }
        
        $this->validate();

        // 現在のターンを取得
        $current_turn = $this->debate->current_turn;

        // 新しいメッセージを作成
        $debateMessage = DebateMessage::create([
            'debate_id' => $this->debate->id,
            'user_id' => Auth::id(),
            'message' => $this->message,
            'turn' => $current_turn,
            'speaking_time' => null, //あとで設定
        ]);


        // メッセージをブロードキャスト
        broadcast(new DebateMessageSent($debateMessage))->toOthers();

        // メッセージ入力フィールドをリセット
        $this->message = '';

        // チャットの自動スクロールをトリガー
        $this->dispatch('messageSent');
    }


    // public function receiveMessage($debateMessage)
    // {
    //
    // }

    public function render()
    {
        // ディベートメッセージを取得
        $messages = $this->debate->messages()->with('user')->orderBy('created_at')->get();

        return view('livewire.debate-chat', [
            'messages' => $messages,
            'isCurrentSpeaker' => $this->isCurrentSpeaker,
        ]);
    }
}
