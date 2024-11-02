<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\RoomJoined;
use App\Events\RoomUpdated;
use App\Events\StatusUpdated;



class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::where('status', 'waiting')->get();
        return view('rooms.index', ['rooms' => $rooms]);
    }

    public function create()
    {
        return view('rooms.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'topic' => 'required|string|max:255',
        ]);

        $room = Room::create([
            'name' => $validatedData['name'],
            'topic' => $validatedData['topic'],
            'created_by' => Auth::id(), // 認証済みユーザーのIDを取得
        ]);

        // ルーム作成後、自動的にルームに参加させる
        $room->users()->attach(Auth::id(), ['side' => 'affirmative']); // 作成者は肯定側

        return redirect()->route('rooms.show', ['room' => $room]);
    }

    public function show(Room $room)
    {
        $isCreator = auth()->user()->id === $room->created_by;
        return view('rooms.show', [
            'room' => $room,
            'isCreator' => $isCreator,
        ]);
    }

    public function startDebate()
    {
        // 参加者が2名揃っているか確認
        if ($this->room->users->count() !== 2) {
            session()->flash('error', '参加者が2名揃っていません。');
            return;
        }

        // すでにディベートが開始されているか確認
        if ($this->room->status !== 'ready') {
            session()->flash('error', 'ディベートはすでに開始されています。');
            return;
        }

        // 肯定側と否定側のユーザーを取得
        $affirmativeUser = $this->room->users->firstWhere('pivot.side', 'affirmative');
        $negativeUser = $this->room->users->firstWhere('pivot.side', 'negative');

        // ディベートレコードを作成
        $debate = Debate::create([
            'room_id' => $this->room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
        ]);

        // ルームのステータスを更新
        $this->room->update(['status' => 'debating']);

        // ディベート中ページにリダイレクト
        return redirect()->route('debate.show', ['room' => $this->room]);
    }

    public function exitRoom(Room $room)
    {
        $room->users()->detach(auth()->user()->id); // この行を追加
        if (auth()->user()->id === $room->created_by) {
            // $room->users()->detach();
            $room->delete();
        } else {
        }
        return redirect()->route('rooms.index');
    }

    public function joinRoom(Request $request, Room $room)
    {
        $side = $request->input('side'); // 参加する側 (肯定側 or 否定側)

        if ($room->users->contains(auth()->user())) {
            // すでに参加している場合はエラーメッセージを表示
            return redirect()->back()->with('error', 'すでにこのルームに参加しています。');
        }

        if ($room->users->count() >= 2) {
            // 定員オーバーの場合はエラーメッセージを表示
            return redirect()->back()->with('error', 'このルームは定員に達しています。');
        }

        $room->users()->attach(auth()->user(), ['side' => $side]);
        // リレーションを最新の状態に更新
        $room->refresh();
        // 参加者が2人揃ったらステータスを更新
        if ($room->users->count() === 2) {
            $room->update(['status' => 'ready']);

            // ルーム参加イベントをブロードキャスト
            broadcast(new StatusUpdated($room))->toOthers();
        }


        return redirect()->route('rooms.show', $room)->with('success', 'ルームに参加しました。');
    }
}
