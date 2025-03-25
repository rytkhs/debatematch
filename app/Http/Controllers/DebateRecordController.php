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

        $debatesQuery = Debate::with(['room', 'affirmativeUser', 'negativeUser', 'evaluations'])
            ->whereHas('room', function ($query) {
                $query->where('status', 'finished');
            })
            ->where(function ($query) use ($user) {
                $query->where('affirmative_user_id', $user->id)
                    ->orWhere('negative_user_id', $user->id);
            })
            ->whereHas('evaluations'); // evaluationsが存在するDebateに絞り込む


        if ($result !== 'all') {
            if ($result === 'win') {
                $debatesQuery->where(function ($query) use ($user) {
                    $query->where(function ($q) use ($user) {
                        $q->whereHas('evaluations', function ($qe) {
                            $qe->where('winner', 'affirmative');
                        })->where('affirmative_user_id', $user->id);
                    })->orWhere(function ($q) use ($user) {
                        $q->whereHas('evaluations', function ($qe) {
                            $qe->where('winner', 'negative');
                        })->where('negative_user_id', $user->id);
                    });
                });
            } else {
                $debatesQuery->where(function ($query) use ($user) {
                    $query->where(function ($q) use ($user) {
                        $q->whereHas('evaluations', function ($qe) {
                            $qe->where('winner', 'negative');
                        })->where('affirmative_user_id', $user->id);
                    })->orWhere(function ($q) use ($user) {
                        $q->whereHas('evaluations', function ($qe) {
                            $qe->where('winner', 'affirmative');
                        })->where('negative_user_id', $user->id);
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

        // 統計情報を取得
        $totalDebates = (clone $debatesQuery)->count();

        $wins = (clone $debatesQuery)->where(function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('evaluations', function ($qe) {
                    $qe->where('winner', 'affirmative');
                })->where('affirmative_user_id', $user->id);
            })->orWhere(function ($q) use ($user) {
                $q->whereHas('evaluations', function ($qe) {
                    $qe->where('winner', 'negative');
                })->where('negative_user_id', $user->id);
            });
        })->count();

        $losses = (clone $debatesQuery)->where(function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('evaluations', function ($qe) {
                    $qe->where('winner', 'negative');
                })->where('affirmative_user_id', $user->id);
            })->orWhere(function ($q) use ($user) {
                $q->whereHas('evaluations', function ($qe) {
                    $qe->where('winner', 'affirmative');
                })->where('negative_user_id', $user->id);
            });
        })->count();

        $winRate = $totalDebates > 0 ? round(($wins / $totalDebates) * 100) : 0;

        $stats = [
            'total' => $totalDebates,
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $winRate
        ];

        // ページネーションの適用
        $debates = $debatesQuery->paginate(6)->appends([
            'side' => $side,
            'result' => $result,
            'sort' => $sort,
            'keyword' => $keyword,
        ]);

        return view('records.index', compact('debates', 'side', 'result', 'sort', 'keyword', 'stats'));
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
