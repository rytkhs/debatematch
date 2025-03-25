<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomUser;
use App\Models\Debate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\UserJoinedRoom;
use App\Events\UserLeftRoom;
use App\Events\StatusUpdated;
use Carbon\Carbon;


class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::where('status', Room::STATUS_WAITING)->get();
        return view('rooms.index', compact('rooms'));
    }

    public function create()
    {
        return view('rooms.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'  => 'required|string|max:255',
            'topic' => 'required|string|max:255',
            'side'  => 'required|in:affirmative,negative',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $room = Room::create([
            'name'       => $validatedData['name'],
            'topic'      => $validatedData['topic'],
            'remarks'    => $validatedData['remarks'] ?? null,
            'status' => Room::STATUS_WAITING,
            'created_by' => Auth::id(),
        ]);

        // ユーザーが選択した側でルームに参加させる
        $room->users()->attach(Auth::id(), [
            'side' => $validatedData['side'],
            'role' => RoomUser::ROLE_CREATOR,
            'status' => RoomUser::STATUS_CONNECTED,
        ]);

        return redirect()->route('rooms.show', compact('room'));
    }

    public function preview(Room $room)
    {
        // 参加しているユーザーはルームページにリダイレクト
        if ($room->users->contains(auth()->user())) {
            return redirect()->route('rooms.show', $room);
        }

        return view('rooms.preview', compact('room'));
    }

    public function show(Room $room)
    {
        $isCreator = auth()->user()->id === $room->created_by;
        return view('rooms.show', [
            'room' => $room,
            'isCreator' => $isCreator,
        ]);
    }

    public function join(Request $request, Room $room)
    {
        $side = $request->input('side'); //肯定側 or 否定側

        if ($room->users->contains(auth()->user())) {
            // すでに参加しているか確認
            return redirect()->back()->with('error', 'すでにこのルームに参加しています。');
        }

        // 既に参加者がいるか確認
        if ($room->users()->wherePivot('role', RoomUser::ROLE_PARTICIPANT)->exists()) {
            return redirect()->route('rooms.index')->with('error', 'このルームは既に満員です。');
        }

        // ルームが待機中または準備完了状態でない場合はエラー
        if (!in_array($room->status, [Room::STATUS_WAITING])) {
            return redirect()->route('rooms.index')->with('error', 'このルームには参加できません。');
        }

        // $room->users()->attach(auth()->user(), ['side' => $side]);
        // 参加者として登録
        $room->users()->attach(Auth::id(), [
            'side' => $side,
            'role' => RoomUser::ROLE_PARTICIPANT,
            'status' => RoomUser::STATUS_CONNECTED,
        ]);

        // ルームの状態を "準備完了" に更新
        $room->updateStatus(Room::STATUS_READY);

        $room->refresh();

        // ホストに参加者が参加したことを通知
        broadcast(new UserJoinedRoom($room, Auth::user()))->toOthers();
        return redirect()->route('rooms.show', $room)->with('success', 'ルームに参加しました。');
    }

    public function exit(Room $room)
    {
        // ユーザーが認証されていない場合は処理しない
        if (!auth()->check()) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // ユーザーがルームに参加していない場合は処理しない
        if (!$room->users->contains(auth()->user())) {
            // return redirect()->route('rooms.index');
            return response()->json(['message' => 'User not in room'], 400);
        }

        // ユーザーをルームから退出させる
        $room->users()->detach(auth()->user()->id);

        // 退出後のステータス更新
        if ($room->status == Room::STATUS_READY) {
            // 参加者が退出した場合、状態を "待機中" に戻す
            $room->updateStatus(Room::STATUS_WAITING);
        }
        $room->refresh();

        broadcast(new UserLeftRoom($room, Auth::user()))->toOthers();
        // ルーム作成者が退出した場合、ルームを削除
        if (auth()->user()->id === $room->created_by) {
            $room->delete();
            return redirect()->route('welcome')->with('message', 'ルームを削除しました。');
        } else {
            return redirect()->route('rooms.preview', $room)->with('message', 'ルームから退出しました。');
        }
    }
}
