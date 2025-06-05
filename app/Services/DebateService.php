<?php

namespace App\Services;

use App\Events\TurnAdvanced;
use App\Events\DebateFinished;
use App\Events\DebateTerminated;
use App\Events\EarlyTerminationRequested;
use App\Events\EarlyTerminationAgreed;
use App\Events\EarlyTerminationDeclined;
use App\Jobs\EvaluateDebateJob;
use App\Jobs\AdvanceDebateTurnJob;
use App\Jobs\GenerateAIResponseJob;
use App\Jobs\EarlyTerminationTimeoutJob;
use App\Models\Debate;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DebateService
{
    protected int $aiUserId;

    public function __construct()
    {
        $this->aiUserId = (int)config('app.ai_user_id', 1);
    }


    /**
     * フォーマットを取得
     */
    public function getFormat(Debate $debate): array
    {
        $locale = app()->getLocale();
        return Cache::remember("debate_format_{$debate->room_id}_{$locale}", 60, function () use ($debate) {
            return $debate->room->getDebateFormat();
        });
    }

    /**
     * ディベートを開始し、最初のターンをセットアップ
     */
    public function startDebate(Debate $debate): void
    {
        DB::transaction(function () use ($debate) {
            $firstTurn = 1;
            $format = $this->getFormat($debate);

            if (!isset($format[$firstTurn])) {
                Log::error("First turn not found in format for debate {$debate->id}");
                throw new \Exception("Debate format is invalid.");
            }

            $duration = $format[$firstTurn]['duration'] ?? 0;
            // 最初のターンには少しバッファを持たせる
            $duration += 8;

            $debate->current_turn = $firstTurn;
            $debate->turn_end_time = Carbon::now()->addSeconds($duration);
            $debate->save();

            // --- AIディベート用の処理 ---
            $isAITurn = false;
            if ($debate->room->is_ai_debate) {
                $firstTurnSpeakerId = ($format[$firstTurn]['speaker'] === 'affirmative')
                    ? $debate->affirmative_user_id
                    : $debate->negative_user_id;
                if ($firstTurnSpeakerId === $this->aiUserId) {
                    $isAITurn = true;
                }
            }

            DB::afterCommit(function () use ($debate, $firstTurn, $isAITurn) {
                if ($isAITurn) {
                    // 最初のターンがAIの場合、応答生成ジョブを即時実行
                    GenerateAIResponseJob::dispatch($debate->id, $firstTurn);
                    Log::info('Dispatched GenerateAIResponseJob for first turn (AI)', ['debate_id' => $debate->id]);
                    // AIのターンでも、次のターンに進むためのジョブはスケジュールする
                    AdvanceDebateTurnJob::dispatch($debate->id, $firstTurn)->delay($debate->turn_end_time);
                    Log::info('Scheduled AdvanceDebateTurnJob for end of AI first turn', ['debate_id' => $debate->id]);
                } else {
                    // 最初のターンがユーザーの場合、ターン終了時に進行ジョブをスケジュール
                    AdvanceDebateTurnJob::dispatch($debate->id, $firstTurn)->delay($debate->turn_end_time);
                    Log::info('Scheduled AdvanceDebateTurnJob for end of user first turn', ['debate_id' => $debate->id]);
                }

                $eventData = $this->createEventData($debate, $firstTurn);
                broadcast(new TurnAdvanced($debate, $eventData));
                Log::info('Broadcasted TurnAdvanced for first turn', ['debate_id' => $debate->id]);
            });
        });
    }

    /**
     * 次のターンを取得
     */
    public function getNextTurn(Debate $debate): ?int
    {
        $nextTurn = $debate->current_turn + 1;
        return isset($this->getFormat($debate)[$nextTurn]) ? $nextTurn : null;
    }

    /**
     * 現在のターンを更新し、終了時刻を再計算・保存
     */
    public function updateTurn(Debate $debate, int $nextTurn): void
    {
        $format = $this->getFormat($debate);
        if (!isset($format[$nextTurn])) {
            Log::error("Next turn ({$nextTurn}) not found in format for debate {$debate->id}");
            throw new \Exception("Debate format is invalid or turn number is out of bounds.");
        }
        $debate->current_turn = $nextTurn;
        // 2ターン目以降は少しバッファを持たせる
        $duration = $format[$nextTurn]['duration'] + 2;
        $debate->turn_end_time = Carbon::now()->addSeconds($duration);
        $debate->save();
    }

    /**
     * ディベートを終了し、roomのステータスを変更
     */
    public function finishDebate(Debate $debate): void
    {
        DB::transaction(function () use ($debate) {
            if ($debate->room && $debate->room->status === Room::STATUS_DEBATING) {
                $debate->room->updateStatus(Room::STATUS_FINISHED);
            }

            $debate->update(['turn_end_time' => null]);

            DB::afterCommit(function () use ($debate) {
                try {
                    broadcast(new DebateFinished($debate->id));
                    EvaluateDebateJob::dispatch($debate->id);
                    Log::info('DebateFinished broadcasted and EvaluateDebateJob dispatched after commit.', ['debate_id' => $debate->id]);
                } catch (\Exception $e) {
                    Log::error('Error during post-commit actions in finishDebate', [
                        'debate_id' => $debate->id,
                        'error' => $e->getMessage()
                    ]);
                }
            });
        });
    }

    /**
     * 現在のターンが質疑ターンかどうかを判定する
     */
    public function isQuestioningTurn(Debate $debate, int $turnNumber): bool
    {
        $format = $this->getFormat($debate);
        $turnInfo = $format[$turnNumber] ?? null;

        if (!$turnInfo) {
            return false;
        }

        return ($turnInfo['is_questions'] ?? false);
    }

    /**
     * Debateを次のターンへ進める。
     */
    public function advanceToNextTurn(Debate $debate, ?int $expectedTurn = null): void
    {
        try {
            Log::debug('Attempting to advance turn', [
                'debate_id' => $debate->id,
                'current_turn_in_db' => $debate->current_turn,
                'expected_turn' => $expectedTurn,
                'room_status' => $debate->room->status ?? 'N/A'
            ]);

            // ステータスチェックとターン一致チェック
            if (!$debate->room || $debate->room->status !== Room::STATUS_DEBATING) {
                Log::info('Skipping turn advance: Debate not in debating status.', [
                    'debate_id' => $debate->id,
                    'room_status' => $debate->room ? $debate->room->status : 'null'
                ]);
                return;
            }
            if ($expectedTurn !== null && $debate->current_turn !== $expectedTurn) {
                Log::info('Skipping turn advance: Turn mismatch.', [
                    'debate_id' => $debate->id,
                    'current_turn_in_db' => $debate->current_turn,
                    'expected_turn' => $expectedTurn
                ]);
                return;
            }

            // 次のターン番号を取得
            $nextTurn = $this->getNextTurn($debate);

            DB::transaction(function () use ($debate, $nextTurn) {
                if ($nextTurn) {
                    $this->updateTurn($debate, $nextTurn);

                    // --- AI対戦用の処理分岐 ---
                    $isAITurn = false;
                    $isQuestioningTurn = false;
                    if ($debate->room->is_ai_debate) {
                        $format = $this->getFormat($debate); // フォーマット再取得
                        $nextTurnSpeakerId = ($format[$nextTurn]['speaker'] === 'affirmative')
                            ? $debate->affirmative_user_id
                            : $debate->negative_user_id;
                        if ($nextTurnSpeakerId === $this->aiUserId) {
                            $isAITurn = true;
                        }
                        $isQuestioningTurn = $this->isQuestioningTurn($debate, $nextTurn);
                    }

                    DB::afterCommit(function () use ($debate, $nextTurn, $isAITurn, $isQuestioningTurn) {
                        try {
                            $eventData = $this->createEventData($debate, $nextTurn);
                            broadcast(new TurnAdvanced($debate, $eventData));

                            if ($isAITurn) {
                                // AIターンなら応答生成ジョブを実行
                                GenerateAIResponseJob::dispatch($debate->id, $nextTurn)->delay(now()->addSeconds(5));
                                Log::info('Dispatched GenerateAIResponseJob for AI turn', [
                                    'debate_id' => $debate->id,
                                    'turn' => $nextTurn,
                                    'is_questioning' => $isQuestioningTurn
                                ]);


                                AdvanceDebateTurnJob::dispatch($debate->id, $nextTurn)
                                    ->delay($debate->turn_end_time);
                                Log::info('Scheduled AdvanceDebateTurnJob for end of AI turn', [
                                    'debate_id' => $debate->id,
                                    'turn' => $nextTurn
                                ]);
                            } else {
                                // 次がユーザーターンならターン終了時の進行ジョブをスケジュール
                                AdvanceDebateTurnJob::dispatch($debate->id, $nextTurn)->delay($debate->turn_end_time);
                                Log::info('Scheduled AdvanceDebateTurnJob for end of user turn', [
                                    'debate_id' => $debate->id,
                                    'turn' => $nextTurn
                                ]);
                            }

                            Log::info('Broadcasted TurnAdvanced', ['debate_id' => $debate->id, 'next_turn' => $nextTurn]);
                        } catch (\Exception $e) {
                            Log::error('Error during post-commit actions in advanceToNextTurn', [
                                'debate_id' => $debate->id,
                                'next_turn' => $nextTurn,
                                'error' => $e->getMessage()
                            ]);
                            // エラー発生時はディベートを強制終了
                            try {
                                if ($debate->room && $debate->room->status === Room::STATUS_DEBATING) {
                                    $this->terminateDebate($debate);
                                    Log::warning('Debate forcibly terminated due to error in advanceToNextTurn afterCommit', [
                                        'debate_id' => $debate->id,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            } catch (\Exception $termException) {
                                Log::critical('Failed to forcibly terminate debate after error in advanceToNextTurn afterCommit', [
                                    'debate_id' => $debate->id,
                                    'error' => $termException->getMessage()
                                ]);
                            }
                        }
                    });
                } else {
                    // 最後のターンが終了した場合、ディベートを終了
                    $this->finishDebate($debate);
                    Log::info('Finishing debate as next turn is null', ['debate_id' => $debate->id]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Unexpected error during turn advancement', [
                'debate_id' => $debate->id,
                'expected_turn' => $expectedTurn,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 深刻なエラーの場合はディベートを強制終了
            try {
                if ($debate->room && $debate->room->status === Room::STATUS_DEBATING) {
                    $this->terminateDebate($debate);
                }
            } catch (\Exception $termException) {
                Log::critical('Failed to terminate debate after error in advanceToNextTurn', [
                    'debate_id' => $debate->id,
                    'error' => $termException->getMessage()
                ]);
            }
        }
    }

    /**
     * ディベートを強制終了する
     */
    public function terminateDebate(Debate $debate): void
    {
        DB::transaction(function () use ($debate) {
            // 終了/強制終了/削除済みでないか確認
            if ($debate->room && !in_array($debate->room->status, [Room::STATUS_FINISHED, Room::STATUS_TERMINATED, Room::STATUS_DELETED])) {
                $debate->room->updateStatus(Room::STATUS_TERMINATED);
            }

            if ($debate->turn_end_time !== null) {
                $debate->update(['turn_end_time' => null]);
            }

            DB::afterCommit(function () use ($debate) {
                try {
                    broadcast(new DebateTerminated($debate));
                    Log::info('Broadcasted DebateTerminated', ['debate_id' => $debate->id]);
                } catch (\Exception $e) {
                    Log::error('Error broadcasting DebateTerminated', [
                        'debate_id' => $debate->id,
                        'error' => $e->getMessage()
                    ]);
                }
            });
        });
    }

    /**
     * TurnAdvanced イベントに渡すデータを生成するヘルパーメソッド
     */
    private function createEventData(Debate $debate, int $turnNumber): array
    {
        $format = $this->getFormat($debate);
        $turnInfo = $format[$turnNumber] ?? null;

        if (!$turnInfo) {
            Log::warning("Turn info not found for event data generation", ['debate_id' => $debate->id, 'turn' => $turnNumber]);
            return [
                'turn_number' => $turnNumber,
                'current_turn' => $turnNumber,
                'turn_end_time' => $debate->turn_end_time?->timestamp,
                'speaker' => null,
                'is_prep_time' => false,
            ];
        }

        return [
            'turn_number' => $turnNumber,
            'current_turn' => $turnNumber,
            'turn_end_time' => $debate->turn_end_time?->timestamp,
            'speaker' => $turnInfo['speaker'] ?? null,
            // 'turn_name' => $turnInfo['name'] ?? null,
            'is_prep_time' => $turnInfo['is_prep_time'] ?? false,
        ];
    }

    /**
     * 早期終了を提案する
     */
    public function requestEarlyTermination(Debate $debate, int $userId): bool
    {
        try {
            // フリーフォーマットかつディベート中でないと早期終了できない
            if (!$this->isFreeFormat($debate) || $debate->room->status !== Room::STATUS_DEBATING) {
                Log::warning('Early termination request denied: not free format or not debating', [
                    'debate_id' => $debate->id,
                    'user_id' => $userId,
                    'is_free_format' => $this->isFreeFormat($debate),
                    'room_status' => $debate->room->status
                ]);
                return false;
            }

            // 参加者チェック
            if (!$this->canRequestEarlyTermination($debate, $userId)) {
                Log::warning('Early termination request denied: user cannot request', [
                    'debate_id' => $debate->id,
                    'user_id' => $userId
                ]);
                return false;
            }

            // 既に提案中でないかチェック
            $cacheKey = $this->getCacheKey($debate->id);
            if (Cache::has($cacheKey)) {
                Log::warning('Early termination request denied: already requested', [
                    'debate_id' => $debate->id,
                    'user_id' => $userId
                ]);
                return false;
            }

            // キャッシュに状態を保存（1分間）
            $timestamp = now()->toISOString();
            $requestData = [
                'requested_by' => $userId,
                'status' => 'requested',
                'timestamp' => $timestamp
            ];

            Cache::put($cacheKey, $requestData, 90);

            // 1分後にタイムアウト処理を実行するジョブをスケジュール
            EarlyTerminationTimeoutJob::dispatch($debate->id, $userId, $timestamp)
                ->delay(now()->addMinutes(1));

            // イベントをブロードキャスト
            broadcast(new EarlyTerminationRequested($debate->id, $userId));

            Log::info('Early termination requested', [
                'debate_id' => $debate->id,
                'requested_by' => $userId,
                'timeout_scheduled' => true
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error requesting early termination', [
                'debate_id' => $debate->id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 早期終了提案に応答する
     */
    public function respondToEarlyTermination(Debate $debate, int $userId, bool $agree): bool
    {
        try {
            $cacheKey = $this->getCacheKey($debate->id);
            $requestData = Cache::get($cacheKey);

            if (!$requestData) {
                Log::warning('Early termination response denied: no active request', [
                    'debate_id' => $debate->id,
                    'user_id' => $userId
                ]);
                return false;
            }

            // 応答権限チェック
            if (!$this->canRespondToEarlyTermination($debate, $userId, $requestData['requested_by'])) {
                Log::warning('Early termination response denied: user cannot respond', [
                    'debate_id' => $debate->id,
                    'user_id' => $userId
                ]);
                return false;
            }

            // キャッシュから削除
            Cache::forget($cacheKey);

            if ($agree) {
                // 合意の場合、ディベートを終了
                $this->finishDebate($debate);
                broadcast(new EarlyTerminationAgreed($debate->id));

                Log::info('Early termination agreed', [
                    'debate_id' => $debate->id,
                    'responded_by' => $userId
                ]);
            } else {
                // 拒否の場合、ディベート継続
                broadcast(new EarlyTerminationDeclined($debate->id));

                Log::info('Early termination declined', [
                    'debate_id' => $debate->id,
                    'responded_by' => $userId
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error responding to early termination', [
                'debate_id' => $debate->id,
                'user_id' => $userId,
                'agree' => $agree,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 早期終了の状態を取得する
     */
    public function getEarlyTerminationStatus(Debate $debate): array
    {
        $cacheKey = $this->getCacheKey($debate->id);
        $requestData = Cache::get($cacheKey);

        if (!$requestData) {
            return ['status' => 'none'];
        }

        return [
            'status' => $requestData['status'],
            'requested_by' => $requestData['requested_by'],
            'timestamp' => $requestData['timestamp']
        ];
    }

    /**
     * フリーフォーマットかどうかを判定する
     */
    public function isFreeFormat(Debate $debate): bool
    {
        return $debate->room->isFreeFormat();
    }

    /**
     * 早期終了を提案できるかチェック
     */
    private function canRequestEarlyTermination(Debate $debate, int $userId): bool
    {
        return $userId === $debate->affirmative_user_id || $userId === $debate->negative_user_id;
    }

    /**
     * 早期終了提案に応答できるかチェック
     */
    private function canRespondToEarlyTermination(Debate $debate, int $userId, int $requestedBy): bool
    {
        // 提案者でない参加者のみ応答可能
        if ($userId === $requestedBy) {
            return false;
        }

        return $userId === $debate->affirmative_user_id || $userId === $debate->negative_user_id;
    }

    /**
     * 早期終了用のキャッシュキーを生成
     */
    public function getCacheKey(int $debateId): string
    {
        return "early_termination_request_{$debateId}";
    }
}
