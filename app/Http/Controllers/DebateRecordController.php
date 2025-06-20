<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Debate;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\Record\DebateRecordService;

class DebateRecordController extends Controller
{
    public function __construct(
        private DebateRecordService $recordService
    ) {}

    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = $this->recordService->extractFilters($request->all());

        $result = $user->isGuest()
            ? $this->recordService->getDebatesForGuest($user, $filters)
            : $this->recordService->getDebatesForUser($user, $filters);

        // デモユーザーIDの取得ロジックをコントローラーに移動
        $isGuest = $user->isGuest();
        $demoUserIds = [];

        if ($isGuest) {
            $demoEmails = ['demo1@example.com', 'demo2@example.com'];
            $demoUserIds = User::whereIn('email', $demoEmails)->pluck('id')->toArray();
        }

        // フィルター値をビューに渡す
        $viewData = array_merge($result, [
            'currentUser' => $user,
            'isGuest' => $isGuest,
            'demoUserIds' => $demoUserIds,
            'side' => $request->input('side', 'all'),
            'result' => $request->input('result', 'all'),
            'sort' => $request->input('sort', 'newest'),
            'keyword' => $request->input('keyword', ''),
            'isDemo' => $isGuest, // デモモード通知の表示判定
        ]);

        return view('records.index', $viewData);
    }



    /**
     * 特定のディベート詳細を表示する
     */
    public function show(Debate $debate)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$this->recordService->canAccessDebate($debate, $user)) {
            return redirect()->back();
        }

        $debate->load(['room.creator', 'affirmativeUser', 'negativeUser', 'messages.user', 'evaluations']);
        $evaluations = $debate->evaluations;

        return view('records.show', compact('debate', 'evaluations'));
    }
}
