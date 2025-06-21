<?php

namespace App\Livewire\Rooms;

use Livewire\Component;
use App\Models\Room;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use App\Models\Debate;
use App\Events\DebateStarted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DebateService;
use Illuminate\Support\Facades\Lang;

class StartDebateButton extends Component
{
    public Room $room;
    public string $status;
    public bool $isCreator;
    public array $onlineUsers = [];
    protected $debateService;

    public function boot(DebateService $debateService)
    {
        $this->debateService = $debateService;
    }

    public function mount(Room $room): void
    {
        $this->room = $room;
        $room->load('users');
        $this->status = $this->room->status;

        // 初期状態では全ユーザーをオフラインとして設定
        foreach ($this->room->users as $user) {
            $this->onlineUsers[$user->id] = false;
        }
    }

    #[On('echo:private-rooms.{room.id},UserJoinedRoom')]
    #[On('echo:private-rooms.{room.id},UserLeftRoom')]
    public function updateStatus(array $data): void
    {
        $this->status = $data['room']['status'];
    }

    #[On('member-online')]
    public function handleMemberOnline($data): void
    {
        if (isset($data['id'])) {
            $this->onlineUsers[$data['id']] = true;
        }
    }

    #[On('member-offline')]
    public function handleMemberOffline($data): void
    {
        if (isset($data['id'])) {
            $this->onlineUsers[$data['id']] = false;
        }
    }

    public function startDebate()
    {
        // 参加者が2名揃っているか確認
        $this->room->load('users');
        if ($this->room->users->count() !== 2) {
            session()->flash('error', __('flash.start_debate.error.not_enough_participants'));
            return;
        }

        // すでにディベートが開始されているか確認
        if ($this->room->status !== 'ready') {
            session()->flash('error', __('flash.start_debate.error.already_started'));
            return;
        }

        //ルームの作成者か確認
        if (Auth::id() != $this->room->created_by) {
            return redirect()->route('rooms.show', $this->room)->with('error', __('flash.start_debate.unauthorized'));
        }

        // 全参加者がオンラインか確認
        foreach ($this->room->users as $user) {
            if (!($this->onlineUsers[$user->id] ?? false)) {
                session()->flash('error', __('flash.start_debate.error.participants_offline'));
                return;
            }
        }

        return DB::transaction(function () {
            // 肯定側と否定側のユーザーを取得
            $affirmativeUser = $this->room->users->firstWhere('pivot.side', 'affirmative');
            $negativeUser = $this->room->users->firstWhere('pivot.side', 'negative');

            // ディベートレコードを作成
            $debate = Debate::create([
                'room_id' => $this->room->id,
                'affirmative_user_id' => $affirmativeUser->id,
                'negative_user_id' => $negativeUser->id,
            ]);

            $this->debateService->startDebate($debate);
            // ルームのステータスを更新
            $this->room->updateStatus(Room::STATUS_DEBATING);

            // コミット後にDebateStartedイベントを発行
            DB::afterCommit(function () use ($debate) {
                broadcast(new DebateStarted($debate->id, $this->room->id));
                Log::info('DebateStarted broadcasted after commit.', ['debate_id' => $debate->id, 'room_id' => $this->room->id]);
            });

            return;
        });
    }

    public function render()
    {
        return view('livewire.rooms.start-debate-button');
    }
}
