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
    //     return $this->hasMany(DebateEvaluation::class);
    // }

    public static $turns = [
        1 => ['name' => '肯定側立論', 'duration' => 362, 'speaker' => 'affirmative'],
        2 => ['name' => '否定側準備時間', 'duration' => 62, 'speaker' => 'negative'],
        3 => ['name' => '否定側質疑', 'duration' => 182, 'speaker' => 'negative'],
        4 => ['name' => '否定側準備時間', 'duration' => 62, 'speaker' => 'negative'],
        5 => ['name' => '否定側立論', 'duration' => 362, 'speaker' => 'negative'],
        6 => ['name' => '肯定側準備時間', 'duration' => 62, 'speaker' => 'affirmative'],
        7 => ['name' => '肯定側質疑', 'duration' => 182, 'speaker' => 'affirmative'],
        8 => ['name' => '否定側準備時間', 'duration' => 62, 'speaker' => 'negative'],
        9 => ['name' => '否定側第1反駁', 'duration' => 242, 'speaker' => 'negative'],
        10 => ['name' => '肯定側準備時間', 'duration' => 122, 'speaker' => 'affirmative'],
        11 => ['name' => '肯定側第1反駁', 'duration' => 242, 'speaker' => 'affirmative'],
        12 => ['name' => '否定側準備時間', 'duration' => 122, 'speaker' => 'negative'],
        13 => ['name' => '否定側第2反駁', 'duration' => 242, 'speaker' => 'negative'],
        14 => ['name' => '肯定側準備時間', 'duration' => 122, 'speaker' => 'affirmative'],
        15 => ['name' => '肯定側第2反駁', 'duration' => 242, 'speaker' => 'affirmative'],
    ];

    public function startDebate()
    {
        $this->current_turn = 1;
        $this->turn_end_time = Carbon::now()->addSeconds(self::$turns[1]['duration']);
        $this->save();

        // 最初のターン終了時にジョブをスケジュール
        AdvanceDebateTurnJob::dispatch($this->id, 1)->delay($this->turn_end_time);

        // TurnAdvanced イベントをブロードキャスト（最初のターン）
        broadcast(new TurnAdvanced($this))->toOthers();
    }

    public function getNextTurn()
    {
        $next_turn = $this->current_turn + 1;
        if (isset(self::$turns[$next_turn])) {
            return $next_turn;
        }
        return null; // ディベート終了
    }

}
