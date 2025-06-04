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
                $this->dispatch('show-notification', [
                    'type' => 'error',
                    'message' => __('messages.error')
                ]);
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
                        $this->dispatch('show-notification', [
                            'type' => 'success',
                            'message' => __('messages.early_termination_completed')
                        ]);
                    } else {
                        $this->dispatch('show-notification', [
                            'type' => 'error',
                            'message' => __('messages.early_termination_request_failed')
                        ]);
                    }
                } else {
                    $this->dispatch('show-notification', [
                        'type' => 'error',
                        'message' => __('messages.early_termination_request_failed')
                    ]);
                }
            } else {
                // 通常のディベートの場合は提案のみ
                $success = $debateService->requestEarlyTermination($this->debate, $userId);

                if ($success) {
                    $this->dispatch('show-notification', [
                        'type' => 'success',
                        'message' => __('messages.early_termination_requested')
                    ]);
                } else {
                    $this->dispatch('show-notification', [
                        'type' => 'error',
                        'message' => __('messages.early_termination_request_failed')
                    ]);
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

            $this->dispatch('show-notification', [
                'type' => 'error',
                'message' => __('messages.error')
            ]);
        }
    }

    public function respondToEarlyTermination(bool $agree)
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                $this->dispatch('show-notification', [
                    'type' => 'error',
                    'message' => __('messages.error')
                ]);
                return;
            }

            $debateService = app(DebateService::class);
            $success = $debateService->respondToEarlyTermination($this->debate, $userId, $agree);

            if ($success) {
                $message = $agree
                    ? __('messages.early_termination_agreed')
                    : __('messages.early_termination_declined');

                $this->dispatch('show-notification', [
                    'type' => 'success',
                    'message' => $message
                ]);
                $this->refreshStatus();
            } else {
                $this->dispatch('show-notification', [
                    'type' => 'error',
                    'message' => __('messages.early_termination_response_failed')
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Early termination response failed in Livewire', [
                'debate_id' => $this->debate->id,
                'user_id' => Auth::id(),
                'agree' => $agree,
                'error' => $e->getMessage()
            ]);

            $this->dispatch('show-notification', [
                'type' => 'error',
                'message' => __('messages.error')
            ]);
        }
    }

    #[On("echo-private:debate.{debate.id},EarlyTerminationRequested")]
    public function handleEarlyTerminationRequested($event)
    {
        $this->refreshStatus();

        // 提案者でない場合は通知を表示
        if ($event['requestedBy'] !== Auth::id()) {
            $this->dispatch('show-notification', [
                'type' => 'info',
                'message' => __('messages.early_termination_proposal', ['name' => $this->getOpponentName()]),
                'duration' => 10000
            ]);
        }
    }

    #[On('echo-private:debate.{debate.id},early-termination-agreed')]
    public function handleEarlyTerminationAgreed($event)
    {
        $this->refreshStatus();
        $this->dispatch('show-notification', [
            'type' => 'success',
            'message' => __('messages.early_termination_agreed')
        ]);
    }

    #[On('echo-private:debate.{debate.id},EarlyTerminationDeclined')]
    public function handleEarlyTerminationDeclined($event)
    {
        $this->refreshStatus();
        $this->dispatch('show-notification', [
            'type' => 'info',
            'message' => __('messages.early_termination_declined')
        ]);
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
