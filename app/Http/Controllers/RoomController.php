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
// use App\Http\Controllers\SNSController;
use App\Services\SlackNotifier;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class RoomController extends Controller
{
    // protected SNSController $snsController;
    protected SlackNotifier $slackNotifier;

    public function __construct(SlackNotifier $slackNotifier)
    {
        // $this->snsController = $snsController;
        $this->slackNotifier = $slackNotifier;
    }

    public function index()
    {
        $rooms = Room::where('status', Room::STATUS_WAITING)->get();
        return view('rooms.index', compact('rooms'));
    }

    public function create()
    {
        $rawFormats = config('debate.formats');
        $translatedFormats = [];

        // アメリカと日本のフォーマットに分ける
        $usFormats = [
            'format_name_nsda_policy' => $rawFormats['format_name_nsda_policy'],
            'format_name_nsda_ld' => $rawFormats['format_name_nsda_ld'],
            // 'format_name_npda_parliamentary' => $rawFormats['format_name_npda_parliamentary'],
        ];

        $jpFormats = [
            'format_name_nada_high' => $rawFormats['format_name_nada_high'],
            'format_name_henda' => $rawFormats['format_name_henda'],
            'format_name_coda' => $rawFormats['format_name_coda'],
            'format_name_jda' => $rawFormats['format_name_jda'],
        ];

        // ロケールに基づいて順序を決定
        $locale = app()->getLocale();
        if ($locale === 'ja') {
            $sortedFormats = array_merge($jpFormats, $usFormats);
            $languageOrder = ['japanese', 'english']; // 日本語ロケールでは日本語を先に
        } else {
            $sortedFormats = array_merge($usFormats, $jpFormats);
            $languageOrder = ['english', 'japanese']; // その他のロケールでは英語を先に
        }

        foreach ($sortedFormats as $formatKey => $turns) {
            $translatedFormatName = __('debates.' . $formatKey);

            $translatedTurns = [];
            foreach ($turns as $index => $turn) {
                $translatedTurn = $turn;
                $translatedTurn['name'] = __('debates.' . $turn['name']);

                $translatedTurns[$index] = $translatedTurn;
            }
            $translatedFormats[$formatKey] = [
                'name' => $translatedFormatName,
                'turns' => $translatedTurns
            ];
        }

        return view('rooms.create', compact('translatedFormats', 'languageOrder'));
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
            'evidence_allowed' => 'required|boolean',
        ]);

        $customFormatSettings = null;
        if ($validatedData['format_type'] === 'custom') {
            $request->validate([
                'turns' => 'required|array|min:1',
                'turns.*.speaker' => 'required|in:affirmative,negative',
                'turns.*.name' => 'required|string|max:255',
                'turns.*.duration' => 'required|integer|min:1|max:60',
                'turns.*.is_prep_time' => 'nullable|boolean',
                'turns.*.is_questions' => 'nullable|boolean',
            ]);

            // カスタム設定を作成
            $customFormatSettings = [];
            // $turnIndex = 1;

            foreach ($request->input('turns') as $index => $turn) {
                // 分を秒に変換
                $durationInSeconds = (int)$turn['duration'] * 60;

                $isPrepTime = isset($turn['is_prep_time']);
                $isQuestions = isset($turn['is_questions']);

                $customFormatSettings[$index + 1] = [
                    'name' => $turn['name'],
                    'duration' => $durationInSeconds,
                    'speaker' => $turn['speaker'],
                    'is_prep_time' => $isPrepTime,
                    'is_questions' => $isQuestions,
                ];
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
                'evidence_allowed' => $validatedData['evidence_allowed'],
                'created_by' => Auth::id(),
            ]);

            // ユーザーが選択した側でルームに参加させる
            $room->users()->attach(Auth::id(), [
                'side' => $validatedData['side'],
            ]);

            $user = Auth::user();
            $message = "新しいルームが作成されました。\n"
                . "ルーム名: {$room->name}\n"
                . "トピック: {$room->topic}\n"
                . "作成者: {$user->name}"
                . "URL: " . route('rooms.preview', $room);

            // メール通知
            // $this->snsController->sendNotification(
            //     $message,
            //     "【DebateMatch】新規ルーム作成"
            // );
            $result = $this->slackNotifier->send($message);
            if (!$result) {
                Log::warning("Slack通知の送信に失敗しました(ルーム作成)。 Room ID: {$room->id}");
            }

            return redirect()->route('rooms.show', compact('room'))->with('success', __('flash.room.store.success'));
        });
    }

    public function preview(Room $room)
    {
        // 参加しているユーザーはルームページにリダイレクト
        if ($room->users->contains(Auth::user())) {
            return redirect()->route('rooms.show', $room);
        }

        $format = $room->getDebateFormat();

        return view('rooms.preview', compact('room', 'format'));
    }

    public function show(Room $room)
    {
        if ($room->status === Room::STATUS_TERMINATED && $room->created_by === Auth::id()) {
            return redirect()->route('welcome')->with('error', __('flash.debate.show.terminated'));
        }
        // ルームが閉じられている場合
        if ($room->status !== Room::STATUS_WAITING && $room->status !== Room::STATUS_READY) {
            return redirect()->route('rooms.index')->with('error', __('flash.room.show.forbidden'));
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

        $format = $room->getDebateFormat();
        // dd($room->getFormatName(), $room->format_type);
        $isCreator = Auth::user()->id === $room->created_by;
        return view('rooms.show', [
            'room' => $room,
            'isCreator' => $isCreator,
            'format' => $format,
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
            return redirect()->route('rooms.show', $room)->with('error', __('flash.room.join.already_joined'));
        }

        // 既に参加者がいるか確認
        if ($room->users()->where('user_id', '!=', $room->created_by)->exists()) {
            return redirect()->back()->with('error', __('flash.room.join.full'));
        }

        // ルームが待機中でない場合はエラー
        if ($room->status !== Room::STATUS_WAITING) {
            return redirect()->route('rooms.index')->with('error', __('flash.room.join.not_waiting'));
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

            // ルーム作成者の情報を取得
            $creator = $room->creator;
            $message = "ユーザーがルームに参加しました。\n"
                . "ルーム名: {$room->name}\n"
                . "参加者: {$user->name}\n"
                . "ホスト: {$creator->name}\n"
                . "マッチングが成立し、ディベートを開始できる状態になりました。";

            // メール通知のみ送信
            // $this->snsController->sendNotification(
            //     $message,
            //     "【DebateMatch】ルーム参加・マッチング成立"
            // );
            $result = $this->slackNotifier->send($message);
            if (!$result) {
                Log::warning("Slack通知の送信に失敗しました(ユーザー参加)。 Room ID: {$room->id}, User ID: {$user->id}");
            }

            return redirect()->route('rooms.show', $room)->with('success', __('flash.room.join.success'));
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
            return redirect()->route('rooms.index')->with('info', __('flash.room.exit.already_closed'));
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
                    return redirect()->route('welcome')->with('success', __('flash.room.exit.creator_success'));
                } else {
                    return redirect()->route('rooms.index')->with('success', __('flash.room.exit.participant_success'));
                }
            });
        }

        return redirect()->route('rooms.index');
    }
}
