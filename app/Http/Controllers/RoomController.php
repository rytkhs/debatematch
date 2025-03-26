<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\UserJoinedRoom;
use App\Events\UserLeftRoom;
use App\Events\CreatorLeftRoom;
use Illuminate\Support\Facades\DB;
use App\Services\ConnectionManager;


class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::where('status', Room::STATUS_WAITING)->get();
        return view('rooms.index', compact('rooms'));
    }
    public function create()
    {
        $formats = config('debate.formats');

        return view('rooms.create', compact('formats'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'  => 'required|string|max:255',
            'topic' => 'required|string|max:255',
            'side'  => 'required|in:affirmative,negative',
            'remarks' => 'nullable|string|max:1000',
            'language' => 'required|in:japanese,english',
            'format_type' => 'required|string',
        ]);

        $customFormatSettings = null;
        if ($validatedData['format_type'] === 'custom') {
            $request->validate([
                'turns' => 'required|array|min:1',
                'turns.*.speaker' => 'required|in:affirmative,negative',
                'turns.*.name' => 'required|string|max:255',
                'turns.*.duration' => 'required|integer|min:1|max:20',
                'turns.*.is_prep_time' => 'nullable|boolean',
                'turns.*.is_questions' => 'nullable|boolean',
            ]);

            // カスタム設定を作成
            $customFormatSettings = [];
            // $turnIndex = 1;

            foreach ($request->input('turns') as $index => $turn) {
                // 分を秒に変換
                $durationInSeconds = (int)$turn['duration'] * 60;

                $customFormatSettings[$index + 1] = [
                    'name' => $turn['name'],
                    'duration' => $durationInSeconds,
                    'speaker' => $turn['speaker'],
                    'is_prep_time' => isset($turn['is_prep_time']) && $turn['is_prep_time'] == true,
                    'is_questions' => isset($turn['is_questions']) && $turn['is_questions'] == true,
                ];

                $index++;
            }
        }

        $room = Room::create([
            'name'       => $validatedData['name'],
            'topic'      => $validatedData['topic'],
            'remarks'    => $validatedData['remarks'] ?? null,
            'status' => Room::STATUS_WAITING,
            'language' => $validatedData['language'],
            'format_type' => $validatedData['format_type'],
            'custom_format_settings' => $customFormatSettings,
            'created_by' => Auth::id(),
        ]);

        // ユーザーが選択した側でルームに参加させる
        $room->users()->attach(Auth::id(), [
            'side' => $validatedData['side'],
            'role' => RoomUser::ROLE_CREATOR,
        ]);

        return redirect()->route('rooms.show', compact('room'))->with('success', 'ルームを作成しました');
    }

    public function preview(Room $room)
    {
        // 参加しているユーザーはルームページにリダイレクト
        if ($room->users->contains(Auth::user())) {
            return redirect()->route('rooms.show', $room);
        }

        return view('rooms.preview', compact('room'));
    }

    public function show(Room $room)
    {
        if ($room->status === Room::STATUS_TERMINATED && $room->created_by === Auth::id()) {
            return redirect()->route('welcome')->with('error', '切断されました');
        }
        // ルームが閉じられている場合
        if ($room->status !== Room::STATUS_WAITING && $room->status !== Room::STATUS_READY) {
            return redirect()->route('rooms.index')->with('error', 'アクセスできません');
        }
        // 参加していないユーザーはpreviewにリダイレクト
        if (!$room->users->contains(Auth::user())) {
            return redirect()->route('rooms.preview', $room);
        }
        // 接続記録
        $connectionManager = app(ConnectionManager::class);
        $connectionManager->recordInitialConnection(Auth::id(), [
            'type' => 'room',
            'id' => $room->id
        ]);

        $isCreator = Auth::user()->id === $room->created_by;
        return view('rooms.show', [
            'room' => $room,
            'isCreator' => $isCreator,
        ]);
    }

    public function join(Request $request, Room $room)
    {
        $side = $request->input('side'); //肯定側 or 否定側

        if ($room->users->contains(Auth::user())) {
            // すでに参加しているか確認
            return redirect()->route('rooms.show', $room)->with('error', 'すでにこのルームに参加しています。');
        }

        // 既に参加者がいるか確認
        if ($room->users()->wherePivot('role', RoomUser::ROLE_PARTICIPANT)->exists()) {
            return redirect()->back()->with('error', 'このルームは既に満員です。');
        }

        // ルームが待機中でない場合はエラー
        if ($room->status !== Room::STATUS_WAITING) {
            return redirect()->route('rooms.index')->with('error', 'このルームには参加できません。');
        }

        // 参加者として登録
        $room->users()->attach(Auth::id(), [
            'side' => $side,
            'role' => RoomUser::ROLE_PARTICIPANT,
        ]);

        $room->updateStatus(Room::STATUS_READY);

        $room->refresh();

        // ホストに参加者が参加したことを通知
        broadcast(new UserJoinedRoom($room, Auth::user()))->toOthers();
        return redirect()->route('rooms.show', $room)->with('success', 'ルームに参加しました。');
    }

    public function exit(Room $room)
    {
        // ユーザーが認証されていない場合は処理しない
        if (!Auth::check()) {
            return redirect()->route('welcome');
        }

        // ユーザーがルームに参加していない場合は処理しない
        if (!$room->users->contains(Auth::user())) {
            return redirect()->route('rooms.index');
        }

        // ルームのステータスに応じた処理
        if ($room->status === Room::STATUS_DEBATING) {
            return redirect()->back();
        } elseif ($room->status === Room::STATUS_TERMINATED || $room->status === Room::STATUS_DELETED) {
            return redirect()->route('rooms.index')->with('info', 'このルームは既に終了しています。');
        } elseif ($room->status === Room::STATUS_WAITING || $room->status === Room::STATUS_READY) {

            return DB::transaction(function () use ($room) {
                // ユーザーをルームから退出させる
                $room->users()->detach(Auth::user()->id);

                // 退出後のステータス更新
                if ($room->status == Room::STATUS_READY) {
                    $room->updateStatus(Room::STATUS_WAITING);
                }

                // ルーム作成者が退出した場合、ルームを削除
                if (Auth::user()->id === $room->created_by) {
                    // 他の参加者がいるかどうか確認
                    broadcast(new CreatorLeftRoom($room, Auth::user()))->toOthers();
                    $room->updateStatus(Room::STATUS_DELETED);
                    return redirect()->route('welcome')->with('success', 'ルームを削除しました。');
                }

                // 参加者の退出
                broadcast(new UserLeftRoom($room, Auth::user()))->toOthers();
                return redirect()->route('rooms.index')->with('success', 'ルームを退出しました。');
            });
        }
    }
}
