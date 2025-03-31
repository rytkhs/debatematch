<?php

namespace App\Services;

use App\Events\TurnAdvanced;
use App\Events\DebateFinished;
use App\Events\DebateTerminated;
use App\Jobs\EvaluateDebateJob;
use App\Jobs\AdvanceDebateTurnJob;
use App\Models\Debate;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DebateService
{
    /**
     * フォーマットを取得（キャッシュを活用）
     */
    public function getFormat(Debate $debate): array
    {
        return Cache::remember("debate_format_{$debate->room_id}", 60, function () use ($debate) {
            return $debate->room->getDebateFormat();
        });
    }

    /**
     * ディベートを開始し、最初のターンをセットアップ
     */
    public function startDebate(Debate $debate): void
    {
        $firstTurn = 1;
        $format = $this->getFormat($debate);
        $duration = $format[$firstTurn]['duration'] ?? 0;

        // 最初のターンには8秒追加
        $duration += 8;

        $debate->current_turn = $firstTurn;
        $debate->turn_end_time = Carbon::now()->addSeconds($duration);
        $debate->save();

        // 最初のターン終了時にジョブをスケジュール
        AdvanceDebateTurnJob::dispatch($debate->id, $firstTurn)->delay($debate->turn_end_time);

        // TurnAdvanced イベントをブロードキャスト（最初のターン）
        broadcast(new TurnAdvanced($debate));
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
        $debate->current_turn = $nextTurn;
        // 2ターン目以降は2秒追加
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
            if ($debate->room) {
                $debate->room->updateStatus(Room::STATUS_FINISHED);
            }

            $debate->update(['turn_end_time' => null]);
            // コミット後にイベント発行とジョブディスパッチ
            DB::afterCommit(function () use ($debate) {
                broadcast(new DebateFinished($debate->id));
                EvaluateDebateJob::dispatch($debate->id);
                Log::info('DebateFinished broadcasted and EvaluateDebateJob dispatched after commit.', ['debate_id' => $debate->id]);
            });
        });
    }

    /**
     * Debateを次のターンへ進める。
     */
    public function advanceToNextTurn(Debate $debate, ?int $expectedTurn = null): void
    {
        try {
            Log::debug('ターン進行開始', [
                'debate_id' => $debate->id,
                'current_turn' => $debate->current_turn,
                'current_turn_name' => $this->getFormat($debate)[$debate->current_turn]['name'] ?? 'unknown',
                'expected_turn' => $expectedTurn
            ]);

            // 手動で進めた場合とのバッティングチェック
            if ($expectedTurn !== null && $debate->current_turn !== $expectedTurn) {
                Log::info('ターン不一致のため進行をスキップ', [
                    'debate_id' => $debate->id,
                    'current_turn' => $debate->current_turn,
                    'expected_turn' => $expectedTurn
                ]);
                return;
            }

            // ルームのステータスがディベート中かチェック
            if (!$debate->room || $debate->room->status !== Room::STATUS_DEBATING) {
                Log::info('ディベート中でないためターン進行をスキップ', [
                    'debate_id' => $debate->id,
                    'room_status' => $debate->room ? $debate->room->status : 'null'
                ]);
                return;
            }

            // 次のターン番号を取得
            $nextTurn = $this->getNextTurn($debate);

            DB::transaction(function () use ($debate, $nextTurn) {
                if ($nextTurn) {
                    // 次のターンへ
                    $this->updateTurn($debate, $nextTurn);

                    // イベントデータを充実させて、DB再取得を減らす
                    $eventData = [
                        'turn_number' => $nextTurn,
                        'turn_end_time' => $debate->turn_end_time->timestamp,
                        'speaker' => $this->getFormat($debate)[$nextTurn]['speaker'] ?? null,
                        'turn_name' => $this->getFormat($debate)[$nextTurn]['name'] ?? null,
                        'is_prep_time' => $this->getFormat($debate)[$nextTurn]['is_prep_time'] ?? false
                    ];

                    // トランザクションコミット後に実行
                    DB::afterCommit(function () use ($debate, $nextTurn, $eventData) {
                        try {
                            broadcast(new TurnAdvanced($debate, $eventData));

                            AdvanceDebateTurnJob::dispatch($debate->id, $nextTurn)
                                ->delay($debate->turn_end_time);

                            Log::debug(
                                'TurnAdvanced broadcasted and AdvanceDebateTurnJob dispatched after commit.',
                                ['debate_id' => $debate->id, 'next_turn' => $nextTurn]
                            );
                        } catch (\Exception $e) {
                            Log::error('ターン進行イベント処理中にエラーが発生', [
                                'debate_id' => $debate->id,
                                'next_turn' => $nextTurn,
                                'error' => $e->getMessage()
                            ]);
                        }
                    });
                } else {
                    $this->finishDebate($debate);
                }
            });
        } catch (\Exception $e) {
            Log::error('ターン進行処理中に予期せぬエラーが発生', [
                'debate_id' => $debate->id,
                'current_turn' => $debate->current_turn,
                'expected_turn' => $expectedTurn,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 深刻なエラーの場合はディベートを終了
            try {
                $this->terminateDebate($debate);
            } catch (\Exception $termException) {
                Log::critical('ディベート終了処理にも失敗しました', [
                    'debate_id' => $debate->id,
                    'error' => $termException->getMessage()
                ]);
            }
        }
    }

    /**
     * ディベートを強制終了する
     * 評価は行わない
     */
    public function terminateDebate(Debate $debate): void
    {
        DB::transaction(function () use ($debate) {
            if ($debate->room) {
                $debate->room->updateStatus(Room::STATUS_TERMINATED);
            }

            $debate->update(['turn_end_time' => null]);

            // 強制終了イベントをブロードキャスト
            broadcast(new DebateTerminated($debate));
        });
    }
}
