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
use App\Http\Controllers\SMSController;

class RoomController extends Controller
{
    protected SMSController $smsController;

    public function __construct(SMSController $smsController)
    {
        $this->smsController = $smsController;
    }

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
                'turns.*.duration' => 'required|integer|min:1|max:14',
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

        return DB::transaction(function () use ($validatedData, $customFormatSettings) {
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
            ]);

            $adminPhoneNumber = env('SMS_ADMIN_PHONE_NUMBER');
            if ($adminPhoneNumber) {
                $user = Auth::user();
                $message = "新しいルームが作成されました。\n"
                    . "ルーム名: {$room->name}\n"
                    . "トピック: {$room->topic}\n"
                    . "作成者: {$user->name}";

                $this->smsController->sendSms($adminPhoneNumber, $message);
            }

            return redirect()->route('rooms.show', compact('room'))->with('success', 'ルームを作成しました');
        });
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
        if (!Auth::check()) {
            return redirect()->route('welcome');
        }

        $user = Auth::user();
        $side = $request->input('side'); //肯定側 or 否定側

        if ($room->users->contains($user)) {
            // すでに参加しているか確認
            return redirect()->route('rooms.show', $room)->with('error', 'すでにこのルームに参加しています。');
        }

        // 既に参加者がいるか確認
        if ($room->users()->where('user_id', '!=', $room->created_by)->exists()) {
            return redirect()->back()->with('error', 'このルームは既に満員です。');
        }

        // ルームが待機中でない場合はエラー
        if ($room->status !== Room::STATUS_WAITING) {
            return redirect()->route('rooms.index')->with('error', 'このルームには参加できません。');
        }

        return DB::transaction(function () use ($room, $user, $side) {
            // 参加者として登録
            $room->users()->attach($user->id, [
                'side' => $side,
            ]);

            $room->updateStatus(Room::STATUS_READY);
            $room->refresh();

            DB::afterCommit(function () use ($room, $user) {
                // ホストに参加者が参加したことを通知
                broadcast(new UserJoinedRoom($room, $user))->toOthers();
            });

            $adminPhoneNumber = env('SMS_ADMIN_PHONE_NUMBER');
            if ($adminPhoneNumber) {
                // ルーム作成者の情報を取得
                $creator = $room->creator;
                $message = "ユーザーがルームに参加しました。\n"
                    . "ルーム名: {$room->name}\n"
                    . "参加者: {$user->name}\n"
                    . "ホスト: {$creator->name}\n"
                    . "マッチングが成立し、ディベートを開始できる状態になりました。";

                $this->smsController->sendSms($adminPhoneNumber, $message);
            }

            return redirect()->route('rooms.show', $room)->with('success', 'ルームに参加しました。');
        });
    }

    public function exit(Room $room)
    {
        if (!Auth::check()) {
            return redirect()->route('welcome');
        }

        $user = Auth::user();

        // ユーザーがルームに参加していない場合は処理しない
        if (!$room->users->contains($user)) {
            return redirect()->route('rooms.index');
        }

        // ルームのステータスに応じた処理
        if ($room->status === Room::STATUS_DEBATING) {
            // ディベート中の退出は現状許可しない
            return redirect()->back();
        } elseif ($room->status === Room::STATUS_TERMINATED || $room->status === Room::STATUS_DELETED) {
            // 既に終了または削除されたルームからの退出
            return redirect()->route('rooms.index')->with('info', 'このルームは既に終了しています。');
        } elseif ($room->status === Room::STATUS_WAITING || $room->status === Room::STATUS_READY) {
            // 待機中または準備完了状態のルームからの退出処理
            return DB::transaction(function () use ($room, $user) {
                // ユーザーをルームから退出させる
                $room->users()->detach($user->id);

                // 退出後のステータス更新
                if ($room->status == Room::STATUS_READY) {
                    $room->updateStatus(Room::STATUS_WAITING);
                }

                // 作成者退出フラグ
                $isCreator = ($user->id === $room->created_by);

                // ルーム作成者が退出した場合
                if ($isCreator) {
                    // ルームを削除状態に更新
                    $room->updateStatus(Room::STATUS_DELETED);
                }

                // トランザクション成功後にブロードキャスト
                DB::afterCommit(function () use ($room, $user, $isCreator) {
                    if ($isCreator) {
                        // 他の参加者に作成者が退出したことを通知
                        broadcast(new CreatorLeftRoom($room, $user))->toOthers();
                    } else {
                        // 参加者が退出した場合の通知
                        broadcast(new UserLeftRoom($room, $user))->toOthers();
                    }
                });

                if ($isCreator) {
                    return redirect()->route('welcome')->with('success', 'ルームを削除しました。');
                } else {
                    return redirect()->route('rooms.index')->with('success', 'ルームを退出しました。');
                }
            });
        }

        return redirect()->route('rooms.index');
    }
}
