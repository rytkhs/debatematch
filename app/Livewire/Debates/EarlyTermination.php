<?php

namespace App\Livewire\Debates;

use App\Models\Debate;
use App\Services\DebateService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class EarlyTermination extends Component
{
    public Debate $debate;
    public array $earlyTerminationStatus = ['status' => 'none'];
    public bool $isRequester = false;
    public bool $canRequest = false;
    public bool $canRespond = false;
    public bool $isFreeFormat = false;
    public bool $isAiDebate = false;
    public int $aiUserId;

    public function mount(Debate $debate, DebateService $debateService)
    {
        $this->debate = $debate;
        $this->isFreeFormat = $debateService->isFreeFormat($debate);
        $this->isAiDebate = $debate->room->is_ai_debate ?? false;
        $this->aiUserId = (int)config('app.ai_user_id', 1);
        $this->updateStatus($debateService);

        Log::info('EarlyTermination component mounted', [
            'debate_id' => $this->debate->id,
            'is_free_format' => $this->isFreeFormat,
            'is_ai_debate' => $this->isAiDebate,
            'component_id' => $this->getId()
        ]);
    }

    public function requestEarlyTermination()
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                $this->dispatch('showFlashMessage', __('messages.error'), 'error');
                return;
            }

            $debateService = app(DebateService::class);

            // AIディベートの場合は即座終了（内部的にAIが承認）
            if ($this->isAiDebate) {
                // まず提案をリクエスト
                $success = $debateService->requestEarlyTermination($this->debate, $userId);

                if ($success) {
                    // AIが即座に承認
                    $aiUserId = $this->getAiOpponentId();
                    $aiSuccess = $debateService->respondToEarlyTermination($this->debate, $aiUserId, true);

                    if ($aiSuccess) {
                        // AIディベートでは即座に終了するため、完了通知のみ表示
                        $this->dispatch('showFlashMessage', __('messages.early_termination_completed'), 'success');
                    } else {
                        $this->dispatch('showFlashMessage', __('messages.early_termination_request_failed'), 'error');
                    }
                } else {
                    $this->dispatch('showFlashMessage', __('messages.early_termination_request_failed'), 'error');
                }
            } else {
                // 通常のディベートの場合は提案のみ
                $success = $debateService->requestEarlyTermination($this->debate, $userId);

                if ($success) {
                    // 提案者には提案送信の確認のみ表示（相手への通知はWebSocketイベントで行う）
                    $this->dispatch('showFlashMessage', __('messages.early_termination_proposal_sent'), 'info');
                } else {
                    $this->dispatch('showFlashMessage', __('messages.early_termination_request_failed'), 'error');
                }
            }

            $this->refreshStatus();
        } catch (\Exception $e) {
            Log::error('Early termination request failed in Livewire', [
                'debate_id' => $this->debate->id,
                'user_id' => Auth::id(),
                'is_ai_debate' => $this->isAiDebate,
                'error' => $e->getMessage()
            ]);

            $this->dispatch('showFlashMessage', __('messages.error'), 'error');
        }
    }

    public function respondToEarlyTermination(bool $agree)
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                $this->dispatch('showFlashMessage', __('messages.error'), 'error');
                return;
            }

            $debateService = app(DebateService::class);
            $success = $debateService->respondToEarlyTermination($this->debate, $userId, $agree);

            if ($success) {
                // 応答者には応答送信の確認のみ表示（結果はWebSocketイベントで両方に通知）
                $message = $agree
                    ? __('messages.early_termination_response_sent_agree')
                    : __('messages.early_termination_response_sent_decline');

                $this->dispatch('showFlashMessage', $message, 'info');
                $this->refreshStatus();
            } else {
                $this->dispatch('showFlashMessage', __('messages.early_termination_response_failed'), 'error');
            }
        } catch (\Exception $e) {
            Log::error('Early termination response failed in Livewire', [
                'debate_id' => $this->debate->id,
                'user_id' => Auth::id(),
                'agree' => $agree,
                'error' => $e->getMessage()
            ]);

            $this->dispatch('showFlashMessage', __('messages.error'), 'error');
        }
    }

    #[On("echo-private:debate.{debate.id},EarlyTerminationRequested")]
    public function handleEarlyTerminationRequested($event)
    {
        $this->refreshStatus();

        // 提案者でない場合のみ通知を表示
        $requestedBy = $event['requestedBy'] ?? null;
        if ($requestedBy && $requestedBy !== Auth::id()) {
            $this->dispatch('showFlashMessage', __('messages.early_termination_proposal', ['name' => $this->getOpponentName()]), 'info');
        }
    }

    #[On('echo-private:debate.{debate.id},EarlyTerminationAgreed')]
    public function handleEarlyTerminationAgreed($event)
    {
        $this->refreshStatus();

        // 応答者の場合は重複を避けるため、少し遅らせて表示
        if (isset($event['respondedBy']) && $event['respondedBy'] === Auth::id()) {
            // 応答者には遅延して結果を表示（即座の確認通知と重複を避ける）
            $this->dispatch('showDelayedFlashMessage', __('messages.early_termination_agreed_result'), 'success', 1500);
        } else {
            // 提案者には即座に結果を表示
            $this->dispatch('showFlashMessage', __('messages.early_termination_agreed_result'), 'success');
        }
    }

    #[On('echo-private:debate.{debate.id},EarlyTerminationDeclined')]
    public function handleEarlyTerminationDeclined($event)
    {
        $this->refreshStatus();

        // 応答者の場合は重複を避けるため、少し遅らせて表示
        if (isset($event['respondedBy']) && $event['respondedBy'] === Auth::id()) {
            // 応答者には遅延して結果を表示（即座の確認通知と重複を避ける）
            $this->dispatch('showDelayedFlashMessage', __('messages.early_termination_declined_result'), 'info', 1500);
        } else {
            // 提案者には即座に結果を表示
            $this->dispatch('showFlashMessage', __('messages.early_termination_declined_result'), 'info');
        }
    }

    #[On('echo-private:debate.{debate.id},EarlyTerminationExpired')]
    public function handleEarlyTerminationExpired($event)
    {
        $this->refreshStatus();
        // タイムアウトは両方に同じタイミングで表示
        // $this->dispatch('showFlashMessage', __('messages.early_termination_timeout_message'), 'warning');
    }

    public function refreshStatus()
    {
        try {
            $debateService = app(DebateService::class);
            $this->earlyTerminationStatus = $debateService->getEarlyTerminationStatus($this->debate);
            $this->updatePermissions();
        } catch (\Exception $e) {
            Log::error('Failed to refresh early termination status', [
                'debate_id' => $this->debate->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function updateStatus(DebateService $debateService)
    {
        $this->earlyTerminationStatus = $debateService->getEarlyTerminationStatus($this->debate);
        $this->updatePermissions();
    }

    private function updatePermissions()
    {
        $userId = Auth::id();
        $this->canRequest = $this->debate->canRequestEarlyTermination($userId);
        $this->canRespond = $this->debate->canRespondToEarlyTermination($userId);

        if ($this->earlyTerminationStatus['status'] === 'requested') {
            $this->isRequester = ($this->earlyTerminationStatus['requested_by'] ?? null) === $userId;
            $this->canRespond = $this->canRespond && !$this->isRequester;
        } else {
            $this->isRequester = false;
        }
    }

    private function getOpponentName(): string
    {
        $userId = Auth::id();
        if ($userId === $this->debate->affirmative_user_id) {
            return $this->debate->negativeUser->name ?? __('messages.unknown_user');
        } else {
            return $this->debate->affirmativeUser->name ?? __('messages.unknown_user');
        }
    }

    private function getAiOpponentId(): int
    {
        $userId = Auth::id();
        // AIディベートの場合、相手側のユーザーIDがAIのはず
        if ($userId === $this->debate->affirmative_user_id) {
            return $this->debate->negative_user_id;
        } else {
            return $this->debate->affirmative_user_id;
        }
    }

    public function render()
    {
        return view('livewire.debates.early-termination');
    }
}
