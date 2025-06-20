<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Http\Requests\Room\RoomCreationRequest;
use App\Http\Requests\Room\RoomJoinRequest;
use App\Services\Room\FormatManager;
use App\Services\Room\RoomCreationService;
use App\Services\Room\RoomParticipationService;
use App\Services\Connection\ConnectionCoordinator;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    public function __construct(
        private FormatManager $formatManager,
        private RoomCreationService $roomCreationService,
        private RoomParticipationService $roomParticipationService
    ) {}

    public function index()
    {
        $rooms = Room::where('status', Room::STATUS_WAITING)
            ->with(['creator', 'users'])
            ->get();
        return view('rooms.index', compact('rooms'));
    }

    public function create()
    {
        $translatedFormats = $this->formatManager->getTranslatedFormats();
        $languageOrder = $this->formatManager->getLanguageOrder();

        return view('rooms.create', compact('translatedFormats', 'languageOrder'));
    }

    public function store(RoomCreationRequest $request)
    {
        $validatedData = $request->getProcessedData();
        $room = $this->roomCreationService->createRoom($validatedData, Auth::user());

        return redirect()->route('rooms.show', compact('room'))
            ->with('success', __('flash.room.store.success'));
    }

    public function preview(Room $room)
    {
        // 参加しているユーザーはルームページにリダイレクト
        $room->load('users');
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
        $room->load('users');
        if (!$room->users->contains(Auth::user())) {
            return redirect()->route('rooms.preview', $room);
        }
        // 接続記録
        $connectionCoordinator = app(ConnectionCoordinator::class);
        $connectionCoordinator->recordInitialConnection(Auth::id(), [
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

    public function join(RoomJoinRequest $request, Room $room)
    {
        $user = Auth::user();
        $validatedData = $request->validated();

        try {
            $result = $this->roomParticipationService->joinRoom($room, $user, $validatedData['side']);
            return redirect()->route('rooms.show', $result)
                ->with('success', __('flash.room.join.success'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function exit(Room $room)
    {
        if (!Auth::check()) {
            return redirect()->route('welcome');
        }

        $user = Auth::user();

        // ユーザーがルームに参加していない場合は処理しない
        $room->load('users');
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
            $isCreator = ($user->id === $room->created_by);

            try {
                $this->roomParticipationService->leaveRoom($room, $user);

                if ($isCreator) {
                    return redirect()->route('welcome')->with('success', __('flash.room.exit.creator_success'));
                } else {
                    return redirect()->route('rooms.index')->with('success', __('flash.room.exit.participant_success'));
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        return redirect()->route('rooms.index');
    }
}
