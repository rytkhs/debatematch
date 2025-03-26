<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Events\TurnAdvanced;
use App\Events\DebateFinished;
use App\Events\DebateTerminated;
use App\Jobs\EvaluateDebateJob;
use App\Jobs\AdvanceDebateTurnJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Debate extends Model
{
    use HasFactory;

    protected $fillable = ['room_id', 'affirmative_user_id', 'negative_user_id', 'current_turn', 'turn_end_time'];

    protected $casts = ['turn_end_time' => 'datetime'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function affirmativeUser()
    {
        return $this->belongsTo(User::class, 'affirmative_user_id');
    }

    public function negativeUser()
    {
        return $this->belongsTo(User::class, 'negative_user_id');
    }

    public function messages()
    {
        return $this->hasMany(DebateMessage::class);
    }

    public function evaluations()
    {
        return $this->hasOne(DebateEvaluation::class);
    }

    /**
     * フォーマットを取得（キャッシュを活用）
     */
    public function getFormat(): array
    {
        return Cache::remember("debate_format_{$this->room_id}", 60, function () {
            return $this->room->getDebateFormat();
        });
    }

    /**
     * ディベートを開始し、最初のターンをセットアップ
     */
    public function startDebate(): void
    {
        $firstTurn = 1;
        $format = self::getFormat();
        $duration = $format[$firstTurn]['duration'] ?? 0;

        // 最初のターンには8秒追加
        $duration += 8;

        $this->current_turn = $firstTurn;
        $this->turn_end_time = Carbon::now()->addSeconds($duration);
        $this->save();

        // 最初のターン終了時にジョブをスケジュール
        AdvanceDebateTurnJob::dispatch($this->id, $firstTurn)->delay($this->turn_end_time);

        // TurnAdvanced イベントをブロードキャスト（最初のターン）
        broadcast(new TurnAdvanced($this));
    }

    /**
     * 次のターンを取得
     */
    public function getNextTurn(): ?int
    {
        $nextTurn = $this->current_turn + 1;
        return isset(self::getFormat()[$nextTurn]) ? $nextTurn : null;
    }

    /**
     * 現在のターンを更新し、終了時刻を再計算・保存
     */
    public function updateTurn(int $nextTurn): void
    {
        $format = self::getFormat();
        $this->current_turn = $nextTurn;
        // 2ターン目以降は2秒追加
        $duration = $format[$nextTurn]['duration'] + 2;
        $this->turn_end_time = Carbon::now()->addSeconds($duration);
        $this->save();
    }

    /**
     * ディベートを終了し、roomのステータスを変更
     */
    public function finishDebate(): void
    {
        DB::transaction(function () {
            // ディベート終了イベントをブロードキャスト
            broadcast(new DebateFinished($this->id));

            if ($this->room) {
                $this->room->updateStatus(Room::STATUS_FINISHED);
            }

            $this->update(['turn_end_time' => null]);

            // 評価ジョブのディスパッチを追加
            EvaluateDebateJob::dispatch($this->id);
        });
    }

    /**
     * Debateを次のターンへ進める。
     */
    public function advanceToNextTurn(?int $expectedTurn = null): void
    {
        Log::debug('ターン進行開始', [
            'debate_id' => $this->id,
            'current_turn' => $this->current_turn,
            'current_turn_name' => $this->getFormat()[$this->current_turn]['name'],
            'expected_turn' => $expectedTurn
        ]);

        // 手動で進めた場合とのバッティングチェック
        if ($expectedTurn !== null && $this->current_turn !== $expectedTurn) {
            return;
        }

        // ルームのステータスがディベート中かチェック
        if (!$this->room || $this->room->status !== 'debating') {
            return;
        }

        // 次のターン番号を取得
        $nextTurn = $this->getNextTurn();

        DB::transaction(function () use ($nextTurn) {
            if ($nextTurn) {
                // 次のターンへ
                $this->updateTurn($nextTurn);

                // イベントデータを充実させて、DB再取得を減らす
                $eventData = [
                    'turn_number' => $nextTurn,
                    'turn_end_time' => $this->turn_end_time->timestamp,
                    'speaker' => $this->getFormat()[$nextTurn]['speaker'] ?? null,
                    'turn_name' => $this->getFormat()[$nextTurn]['name'] ?? null,
                    'is_prep_time' => $this->getFormat()[$nextTurn]['is_prep_time'] ?? false
                ];

                // TurnAdvanced イベントを拡張データ付きでブロードキャスト
                broadcast(new TurnAdvanced($this, $eventData));

                // 次のターンのジョブをスケジュール
                AdvanceDebateTurnJob::dispatch($this->id, $nextTurn)
                    ->delay($this->turn_end_time);
            } else {
                $this->finishDebate();
            }
        });
    }

    /**
     * ディベートを強制終了する
     * 評価は行わない
     */
    public function terminateDebate(): void
    {
        DB::transaction(function () {
            if ($this->room) {
                $this->room->updateStatus(Room::STATUS_TERMINATED);
            }

            $this->update(['turn_end_time' => null]);

            // 強制終了イベントをブロードキャスト
            broadcast(new DebateTerminated($this));
        });
    }
}
