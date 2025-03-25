<?php

namespace App\Http\Controllers;

use App\Models\Debate;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use App\Events\DebateStarted;

class DebateController extends Controller
{
    public function show(Debate $debate)
    {
        return view('debate.show', compact('debate'));
    }

    public function result(Debate $debate)
    {
        // ユーザーがこのディベートの参加者であることを確認
        $user = Auth::user();
        if ($debate->affirmative_user_id !== $user->id && $debate->negative_user_id !== $user->id) {
            return redirect()->back();
        }

        // 評価データを取得
        $evaluations = $debate->evaluations;

        // メッセージデータを取得
        $messages = $debate->messages()->with('user')->orderBy('created_at')->get();

        // ターン情報を取得
        $turns = $debate->getFormat();

        return view('debate.result', compact('debate', 'messages', 'turns', 'evaluations'));
    }

        $debate->startDebate();
        // ルームのステータスを更新
        $room->updateStatus('debating');

        broadcast(new DebateStarted($debate->id, $room->id));
        return redirect()->back();
    }
}
