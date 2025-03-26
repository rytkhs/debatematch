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

    /**
     * 切断によるディベート強制終了
     */
    public function terminate(Debate $debate)
    {
        // ディベートを強制終了
        $debate->terminateDebate();

        // welcomeページへリダイレクト
        return redirect()->route('welcome')->with('warning', '相手との接続が切断されたため、ディベートを終了しました。');
    }
}
