<?php

namespace App\Http\Controllers;

use App\Models\Debate;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use App\Services\ConnectionManager;
use App\Services\DebateService;

class DebateController extends Controller
{
    protected $debateService;
    protected $connectionManager;

    public function __construct(DebateService $debateService, ConnectionManager $connectionManager)
    {
        $this->debateService = $debateService;
        $this->connectionManager = $connectionManager;
    }

    public function show(Debate $debate)
    {
        if ($debate->room->status === Room::STATUS_FINISHED) {
            return redirect()->route('debate.result', $debate)
                ->with('info', __('flash.debate.show.finished'));
        } elseif ($debate->room->status === Room::STATUS_TERMINATED) {
            return redirect()->route('welcome')->with('error', __('flash.debate.show.terminated'));
        } elseif ($debate->room->status !== Room::STATUS_DEBATING) {
            return redirect()->back();
        }

        // 接続記録
        $this->connectionManager->recordInitialConnection(Auth::id(), [
            'type' => 'debate',
            'id' => $debate->id
        ]);

        return view('debate.show', compact('debate'));
    }

    public function result(Debate $debate)
    {
        // ユーザーがこのディベートの参加者であることを確認
        $user = Auth::user();
        if ($debate->affirmative_user_id !== $user->id && $debate->negative_user_id !== $user->id) {
            return redirect()->back();
        }

        // 評価データを取得
        $evaluations = $debate->evaluations;

        // メッセージデータを取得
        $messages = $debate->messages()->with('user')->orderBy('created_at')->get();

        // ターン情報を取得
        $turns = $this->debateService->getFormat($debate);

        return view('debate.result', compact('debate', 'messages', 'turns', 'evaluations'));
    }

    /**
     * 切断によるディベート強制終了
     */
    public function terminate(Debate $debate)
    {
        // ディベートを強制終了
        $this->debateService->terminateDebate($debate);

        // welcomeページへリダイレクト
        return redirect()->route('welcome')->with('warning', __('flash.debate.terminate.success'));
    }
}
