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

    public function start(Room $room)
    {
        // 参加者が2名揃っているか確認
        if ($room->users->count() !== 2) {
            session()->flash('error', 'ディベーターが揃っていません。');
            return;
        }

        // すでにディベートが開始されているか確認
        if ($room->status !== 'ready') {
            session()->flash('error', 'ディベートはすでに開始されています。');
            return;
        }

        //ルームの作成者か確認
        if (Auth::id() != $room->created_by) {
            return redirect()->route('rooms.show', $room)->with('error', 'ディベートを開始する権限がありません。');
            }

        // 肯定側と否定側のユーザーを取得
        $affirmativeUser = $room->users->firstWhere('pivot.side', 'affirmative');
        $negativeUser = $room->users->firstWhere('pivot.side', 'negative');

        // ディベートレコードを作成
        $debate = Debate::create([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        $debate->startDebate();
        // ルームのステータスを更新
        $room->updateStatus('debating');

        broadcast(new DebateStarted($debate->id, $room->id));
        return redirect()->back();
    }
}
