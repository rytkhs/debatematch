<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Debate;
use App\Models\DebateMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Session;
use App\Events\DebateMessageSent;
use Livewire\Attributes\On;

/**
 * ディベート用のチャット入力フォームコンポーネント
 */
class DebateMessageInput extends Component
{
    public Debate $debate;
    #[Session]
    public string $newMessage = '';
    public bool $isMyTurn = false;
    public ?string $currentSpeaker;
    public ?string $currentTurnName;
    public bool $isPrepTime;
    public bool $isQuestioningTurn = false;

    protected array $rules = [
        'newMessage' => 'required|string|max:5000',
    ];

    public function mount(Debate $debate): void
    {
        $this->debate = $debate;
        $this->syncTurnState();
    }

    /**
     * TurnAdvancedイベントを受信した時に再同期
     */
    #[On("echo-private:debate.{debate.room_id},TurnAdvanced")]
    public function handleTurnAdvanced(array $data): void
    {
        if (!$this->isValidTurnAdvanceData($data)) {
            return;
        }

        $this->reloadDebate($data['debate_id']);
    }

    /**
     * ターンの進行データが有効かチェック
     */
    private function isValidTurnAdvanceData(array $data): bool
    {
        return isset($data['debate_id']);
    }

    /**
     * ディベートモデルの再読み込み
     */
    private function reloadDebate(int $debateId): void
    {
        $this->debate = Debate::with(['affirmativeUser', 'negativeUser'])
            ->find($debateId);

        if ($this->debate) {
            $this->syncTurnState();
        }
    }

    /**
     * 現在のターン情報を同期
     */
    private function syncTurnState(): void
    {
        $turns = $this->debate->getTurns();
        $currentTurn = $this->debate->current_turn;

        $this->setTurnInfo($turns, $currentTurn);
        $this->setUserTurnStatus();
    }

    /**
     * ターン情報の設定
     */
    private function setTurnInfo(array $turns, int $currentTurn): void
    {
        $this->currentSpeaker = $turns[$currentTurn]['speaker'] ?? null;
        $this->currentTurnName = $turns[$currentTurn]['name'] ?? null;
        $this->isPrepTime = $turns[$currentTurn]['is_prep_time'] ?? false;
        $this->isQuestioningTurn = strpos($this->currentTurnName, '質疑') !== false;
    }

    /**
     * ユーザーのターン状態を設定
     */
    private function setUserTurnStatus(): void
    {
        $userId = Auth::id();
        $this->isMyTurn = $this->checkIfUsersTurn($userId);
    }

    /**
     * ユーザーのターンかどうかをチェック
     */
    private function checkIfUsersTurn(int $userId): bool
    {
        return ($this->currentSpeaker === 'affirmative' && $this->debate->affirmativeUser->id === $userId)
            || ($this->currentSpeaker === 'negative' && $this->debate->negativeUser->id === $userId);
    }

    /**
     * メッセージ送信処理
     */
    public function sendMessage(): void
    {
        $this->validate();

        $message = $this->createDebateMessage();
        $this->dispatchEvents($message);
        $this->resetMessageInput();
    }

    /**
     * メッセージの作成
     */
    private function createDebateMessage(): DebateMessage
    {
        return DebateMessage::create([
            'debate_id' => $this->debate->id,
            'user_id' => Auth::id(),
            'message' => $this->newMessage,
            'turn' => $this->debate->current_turn,
        ]);
    }

    /**
     * イベントのディスパッチ
     */
    private function dispatchEvents(DebateMessage $message): void
    {
        $this->dispatch('message-sent');
        broadcast(new DebateMessageSent($message))->toOthers();
        $this->dispatch('scroll-to-bottom');
    }

    /**
     * メッセージ入力のリセット
     */
    private function resetMessageInput(): void
    {
        $this->newMessage = '';
    }

    public function render()
    {
        return view('livewire.debate-message-input');
    }
}
