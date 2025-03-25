<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Debate;
use Illuminate\Support\Facades\Auth;

class DebateRecordController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // フィルターとソートの入力を取得
        $side = $request->input('side', 'all');
        $result = $request->input('result', 'all'); // 'all', 'win', 'lose'
        $sort = $request->input('sort', 'newest'); // 'newest', 'oldest'
        $keyword = $request->input('keyword'); // キーワード検索

        $debatesQuery = Debate::with(['room', 'affirmativeUser', 'negativeUser'])
        ->whereHas('room', function($query) {
            $query->where('status', 'finished');
        })
        ->where(function ($query) use ($user) {
            $query->where('affirmative_user_id', $user->id)
                  ->orWhere('negative_user_id', $user->id);
        });


        if ($result !== 'all') {
            if ($result === 'win') {
                $debatesQuery->where(function ($query) use ($user) {
                    $query->where(function ($q) use ($user) {
                        $q->where('winner', 'affirmative')
                          ->where('affirmative_user_id', $user->id);
                    })->orWhere(function ($q) use ($user) {
                        $q->where('winner', 'negative')
                          ->where('negative_user_id', $user->id);
                    });
                });
            } else {
                $debatesQuery->where(function ($query) use ($user) {
                    $query->where(function ($q) use ($user) {
                        $q->where('winner', 'negative')
                          ->where('affirmative_user_id', $user->id);
                    })->orWhere(function ($q) use ($user) {
                        $q->where('winner', 'affirmative')
                          ->where('negative_user_id', $user->id);
                    });
                });
            }
        }

        // 立場フィルター
        if ($side !== 'all') {
            if ($side === 'affirmative') {
                $debatesQuery->where('affirmative_user_id', $user->id);
            } else {
                $debatesQuery->where('negative_user_id', $user->id);
            }
        }

        // ソート順の適用
        if ($sort === 'newest') {
            $debatesQuery->orderBy('created_at', 'desc');
        } else {
            $debatesQuery->orderBy('created_at', 'asc');
        }

        // キーワード検索の適用
        if ($keyword) {
            $debatesQuery->whereHas('room', function ($query) use ($keyword) {
                $query->where('topic', 'like', '%' . $keyword . '%');
            });
        }


        // ページネーションの適用
        $debates = $debatesQuery->paginate(10)->appends([
            'side' => $side,
            'result' => $result,
            'sort' => $sort,
            'keyword' => $keyword,
        ]);

        return view('records.index', compact('debates', 'side', 'result', 'sort', 'keyword'));
    }


    /**
     * 特定のディベート詳細を表示する
     */
    public function show(Debate $debate)
    {
        $user = Auth::user();

        if ($debate->affirmative_user_id !== $user->id && $debate->negative_user_id !== $user->id) {
            return redirect()->back();
        }

        $debate->load(['room.creator', 'affirmativeUser', 'negativeUser', 'messages.user', 'evaluations']);
        $turns = $debate->getFormat();
        $evaluations = $debate->evaluations;

        return view('records.show', compact('debate', 'evaluations'));
    }
}
