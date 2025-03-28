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


class StartDebateButton extends Component
{
    public Room $room;
    public string $status;
    public bool $isCreator;

    public function mount(Room $room): void
    {
        $this->room = $room;
        $this->status = $this->room->status;
    }

    #[On('echo:rooms.{room.id},UserJoinedRoom')]
    #[On('echo:rooms.{room.id},UserLeftRoom')]
    public function updateStatus(array $data): void
    {
        $this->status = $data['room']['status'];
    }

    public function startDebate()
    {
        // 参加者が2名揃っているか確認
        if ($this->room->users->count() !== 2) {
            session()->flash('error', 'ディベーターが揃っていません。');
            return;
        }

        // すでにディベートが開始されているか確認
        if ($this->room->status !== 'ready') {
            session()->flash('error', 'ディベートはすでに開始されています。');
            return;
        }

        //ルームの作成者か確認
        if (Auth::id() != $this->room->created_by) {
            return redirect()->route('rooms.show', $this->room)->with('error', 'ディベートを開始する権限がありません。');
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

            $debate->startDebate();
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
