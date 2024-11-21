<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Debate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\RoomJoined;
use App\Events\RoomUpdated;
use App\Events\StatusUpdated;
use Carbon\Carbon;


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
            'created_by' => Auth::id(),
        ]);

        // ルーム作成後、自動的にルームに参加させる
        $room->users()->attach(Auth::id(), ['side' => 'affirmative']); // 作成者はいったん肯定側

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

    public function startDebate(Room $room)
    {
        // 参加者が2名揃っているか確認
        if ($room->users->count() !== 2) {
            session()->flash('error', '参加者が2名揃っていません。');
            return;
        }

        // すでにディベートが開始されているか確認
        if ($room->status !== 'ready') {
            session()->flash('error', 'ディベートはすでに開始されています。');
            return;
        }

        //ルームの作成者か確認
        if (auth()->user()->id !== $room->created_by) {
            return redirect()->route('rooms.show', $room);
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
        $room->update(['status' => 'debating']);

         // dd($debate);

        return redirect()->route('debate.show', $debate);
    }

    public function exitRoom(Room $room)
    {
        $room->users()->detach(auth()->user()->id);
        if (auth()->user()->id === $room->created_by) {
            $room->delete();
        } 
        return redirect()->route('rooms.index');
    }

    public function joinRoom(Request $request, Room $room)
    {
        $side = $request->input('side'); //肯定側 or 否定側

        if ($room->users->contains(auth()->user())) {
            // すでに参加しているか確認
            return redirect()->back()->with('error', 'すでにこのルームに参加しています。');
        }

        if ($room->users->count() >= 2) {
            // 定員オーバーか確認
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
