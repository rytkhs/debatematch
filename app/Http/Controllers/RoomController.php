<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'roomName' => 'required|max:255',
            'theme' => 'required|max:255',
            'privacy' => 'required|in:public,private',
        ]);

        // 新しいルームレコードを作成
        $room = new Room;
        $room->name = $validatedData['roomName'];
        $room->topic = $validatedData['theme'];
        $room->status = 'waiting';
        $room->created_by = Auth::id(); // ログインユーザーのIDを取得
        $room->save();

        // 作成されたルームの詳細ページにリダイレクト
        return redirect()->route('rooms.show', $room->id)->with('success', 'ルームが正常に作成されました');
    }
}
