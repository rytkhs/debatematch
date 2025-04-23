<?php

namespace App\Jobs;

use App\Events\DebateMessageSent;
use App\Models\Debate;
use App\Models\DebateMessage;
use App\Services\AIService;
use App\Services\DebateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Facades\DB;
use App\Jobs\AdvanceDebateTurnJob;

class GenerateAIResponseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $debateId;
    public int $currentTurn;

    public $tries = 3;
    public $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(int $debateId, int $currentTurn)
    {
        $this->debateId = $debateId;
        $this->currentTurn = $currentTurn;
    }

    /**
     * Execute the job.
     */
    public function handle(AIService $aiService, DebateService $debateService): void
    {
        Log::info('GenerateAIResponseJob started', ['debate_id' => $this->debateId, 'turn' => $this->currentTurn]);

        try {
            $debate = Debate::with('room', 'messages.user')->find($this->debateId);

            if (!$debate) {
                Log::warning('Debate not found in GenerateAIResponseJob', ['debate_id' => $this->debateId]);
                return;
            }

            $aiUserId = (int)config('app.ai_user_id', 1);

            // 質疑応答ターンの場合
            $isQuestioningTurn = $debateService->isQuestioningTurn($debate, $this->currentTurn);

            // 現在のターンが本当にAIのターンか念のため確認（質疑応答ターンはAIとユーザーが交互に発言するため例外）
            $format = $debateService->getFormat($debate);
            $currentTurnInfo = $format[$this->currentTurn] ?? null;
            $expectedSpeakerId = ($currentTurnInfo['speaker'] === 'affirmative')
                ? $debate->affirmative_user_id
                : $debate->negative_user_id;

            // 準備時間の場合は処理をスキップする
            if ($currentTurnInfo && isset($currentTurnInfo['is_prep_time']) && $currentTurnInfo['is_prep_time']) {
                Log::info('GenerateAIResponseJob skipped: Current turn is prep time.', [
                    'debate_id' => $this->debateId,
                    'turn' => $this->currentTurn,
                ]);
                return;
            }

            // 質疑応答ターンか、AIのターンの場合のみ処理を続行する
            if (!$currentTurnInfo || (!$isQuestioningTurn && $expectedSpeakerId !== $aiUserId)) {
                Log::warning('GenerateAIResponseJob skipped: Not AI turn or invalid turn.', [
                    'debate_id' => $this->debateId,
                    'current_debate_turn' => $debate->current_turn,
                    'job_turn' => $this->currentTurn,
                    'expected_speaker_id' => $expectedSpeakerId,
                    'ai_user_id' => $aiUserId,
                    'is_questioning_turn' => $isQuestioningTurn,
                ]);
                return;
            }

            $aiResponse = $aiService->generateResponse($debate);

            DebateMessage::create([
                'debate_id' => $this->debateId,
                'user_id' => $aiUserId,
                'message' => $aiResponse,
                'turn' => $this->currentTurn,
            ]);

            broadcast(new DebateMessageSent($this->debateId))->toOthers();

            // 質疑応答ターンでない場合はAIの応答後ターンを更新
            if (!$isQuestioningTurn) {
                AdvanceDebateTurnJob::dispatch($debate->id, $this->currentTurn)
                    ->delay(now()->addSeconds(5));

                Log::info('Scheduled automatic turn advance after AI response', [
                    'debate_id' => $this->debateId,
                    'turn' => $this->currentTurn,
                ]);
            }

            Log::info('GenerateAIResponseJob finished successfully', ['debate_id' => $this->debateId, 'turn' => $this->currentTurn]);
        } catch (Throwable $e) {
            Log::error('Error in GenerateAIResponseJob', [
                'debate_id' => $this->debateId,
                'turn' => $this->currentTurn,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            } else {
                $this->handleMaxRetries($debate ?? null, $aiUserId ?? (int)config('app.ai_user_id', 1), $e);
            }
        }
    }

    /**
     * ジョブが最大リトライ回数に達したときの処理
     */
    protected function handleMaxRetries(?Debate $debate, int $aiUserId, Throwable $exception): void
    {
        Log::critical('GenerateAIResponseJob failed after max retries', [
            'debate_id' => $this->debateId,
            'turn' => $this->currentTurn,
            'error' => $exception->getMessage()
        ]);

        // ユーザーにエラーを通知
        if ($debate) {
            $errorMessage = ($debate->room->language ?? 'japanese') === 'japanese'
                ? "申し訳ありません、AIの応答生成中にエラーが発生しました。しばらくしてからもう一度お試しください。"
                : "Sorry, an error occurred while generating the AI response. Please try again later.";

            DebateMessage::create([
                'debate_id' => $this->debateId,
                'user_id' => $aiUserId,
                'message' => $errorMessage . "\n(Error: " . $exception->getMessage() . ")",
                'turn' => $this->currentTurn,
            ]);
            broadcast(new DebateMessageSent($this->debateId));
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('GenerateAIResponseJob ultimately failed', [
            'debate_id' => $this->debateId,
            'turn' => $this->currentTurn,
            'error' => $exception->getMessage()
        ]);
        // $this->handleMaxRetries(Debate::find($this->debateId), config('app.ai_user_id', 1), $exception);
    }
}
