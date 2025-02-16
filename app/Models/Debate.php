<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Events\TurnAdvanced;
use App\Jobs\AdvanceDebateTurnJob;
use Carbon\Carbon;

class Debate extends Model
{
    use HasFactory;

    protected $fillable = ['room_id', 'affirmative_user_id', 'negative_user_id', 'winner', 'current_turn', 'turn_end_time'];

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

    // public function evaluations()
    // {
    //     return $this->hasOne(DebateEvaluation::class);
    // }

    /**
     * debatesテーブルのturn構成を取得
     * config('debate.turns') で管理
     */
    public static function getTurns(): array
    {
        return config('debate.turns', []);
    }

    /**
     * ディベートを開始し、最初のターンをセットアップ
     */
    public function startDebate(): void
    {
        // $this->room->updateStatus('debating');
        $firstTurn = 1;
        $turns = self::getTurns();
        $duration = $turns[$firstTurn]['duration'] ?? 0;

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
        return isset(self::getTurns()[$nextTurn]) ? $nextTurn : null;
    }

    /**
     * 現在のターンを更新し、終了時刻を再計算・保存
     */
    public function updateTurn(int $nextTurn): void
    {
        $turns = self::getTurns();
        $this->current_turn = $nextTurn;
        $this->turn_end_time = Carbon::now()->addSeconds($turns[$nextTurn]['duration']);
        $this->save();
    }

    /**
     * ディベートを終了し、roomのステータスを変更
     */
    public function finishDebate(): void
    {
        if ($this->room) {
            $this->room->updateStatus('finished');
        }
        $this->update(['turn_end_time' => null]);
        // EvaluateDebateJob::dispatch($this->id);
        // broadcast(new DebateFinished($this))->toOthers();
    }

    /**
     * Debateを次のターンへ進める。
     */
    public function advanceToNextTurn(?int $expectedTurn = null): void
    {
        // 手動で進めた場合とのバッティングチェック
        if ($expectedTurn !== null && $this->current_turn !== $expectedTurn) {
            return;
        }

        // ルームのステータスがディベート中かチェック
        if (!$this->room || $this->room->status !== 'debating') {
            return;
        }

        // 次のターン番号が取得できればターン更新、なければ終了
        $nextTurn = $this->getNextTurn();
        if ($nextTurn) {
            // 次のターンへ
            $this->updateTurn($nextTurn);

            // TurnAdvanced イベント (Debateモデルを渡す)
            broadcast(new TurnAdvanced($this));
            // 次のターンのジョブをスケジュール
            AdvanceDebateTurnJob::dispatch($this->id, $nextTurn)
                ->delay($this->turn_end_time);
        } else {
            // 最終ターンを経過した場合はディベート終了
            $this->finishDebate();
        }
    }
}
