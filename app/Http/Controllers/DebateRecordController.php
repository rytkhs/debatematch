<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Debate;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\DebateService;

class DebateRecordController extends Controller
{
    public function __construct(
        private DebateRecordService $recordService
    ) {}

    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = $this->getFilters($request);

        if ($user->isGuest()) {
            return $this->handleGuestUser($request, $filters, $user);
        }

        return $this->handleRegularUser($request, $filters, $user);
    }

    /**
     * リクエストからフィルター情報を取得
     */
    private function getFilters(Request $request): array
    {
        return [
            'side' => $request->input('side', 'all'),
            'result' => $request->input('result', 'all'),
            'sort' => $request->input('sort', 'newest'),
            'keyword' => $request->input('keyword')
        ];
    }

    /**
     * 通常ユーザーの処理
     */
    private function handleRegularUser(Request $request, array $filters, User $user)
    {
        $query = $this->buildBaseQuery($user);
        $this->applyFilters($query, $filters, $user);
        $this->applySorting($query, $filters['sort']);

        $stats = $this->calculateStats($query, $user);
        $debates = $query->paginate(6)->appends($filters);

        return view('records.index', compact('debates', 'stats') + $filters);
    }

    /**
     * ゲストユーザーの処理
     */
    private function handleGuestUser(Request $request, array $filters, User $user)
    {
        $demoUserIds = $this->getDemoUserIds();

        if ($demoUserIds->isEmpty()) {
            return $this->handleRegularUser($request, $filters, $user);
        }

        $query = $this->buildGuestQuery($user, $demoUserIds);
        $this->applyGuestFilters($query, $filters, $user, $demoUserIds);
        $this->applySorting($query, $filters['sort']);

        $stats = $this->calculateGuestStats($user, $demoUserIds);
        $debates = $query->paginate(6)->appends($filters);
        $isDemo = $this->isOnlyDemoDebates($debates, $user);

        return view('records.index', compact('debates', 'stats', 'isDemo') + $filters);
    }

    /**
     * デモユーザーのIDを取得
     */
    private function getDemoUserIds()
    {
        return User::whereIn('email', [
            'demo1@example.com',
            'demo2@example.com',
        ])->pluck('id');
    }

    /**
     * 基本的なディベートクエリを構築
     */
    private function buildBaseQuery(User $user)
    {
        return Debate::with(['room', 'affirmativeUser', 'negativeUser', 'evaluations'])
            ->whereHas('room', fn($q) => $q->where('status', 'finished'))
            ->where(fn($q) => $q->where('affirmative_user_id', $user->id)
                ->orWhere('negative_user_id', $user->id))
            ->whereHas('evaluations');
    }

    /**
     * ゲストユーザー用のクエリを構築
     */
    private function buildGuestQuery(User $user, $demoUserIds)
    {
        return Debate::with(['room', 'affirmativeUser', 'negativeUser', 'evaluations'])
            ->whereHas('room', fn($q) => $q->where('status', 'finished'))
            ->where(function ($query) use ($demoUserIds, $user) {
                $query->where(function ($q) use ($demoUserIds) {
                    $q->whereIn('affirmative_user_id', $demoUserIds)
                        ->orWhereIn('negative_user_id', $demoUserIds);
                })->orWhere(function ($q) use ($user) {
                    $q->where('affirmative_user_id', $user->id)
                        ->orWhere('negative_user_id', $user->id);
                });
            })
            ->whereHas('evaluations');
    }

    /**
     * フィルターを適用
     */
    private function applyFilters($query, array $filters, User $user)
    {
        $this->applyResultFilter($query, $filters['result'], $user);
        $this->applySideFilter($query, $filters['side'], $user);
        $this->applyKeywordFilter($query, $filters['keyword']);
    }

    /**
     * ゲストユーザー用フィルターを適用
     */
    private function applyGuestFilters($query, array $filters, User $user, $demoUserIds)
    {
        $this->applyGuestResultFilter($query, $filters['result'], $user, $demoUserIds);
        $this->applyGuestSideFilter($query, $filters['side'], $user, $demoUserIds);
        $this->applyKeywordFilter($query, $filters['keyword']);
    }

    /**
     * 結果フィルターを適用
     */
    private function applyResultFilter($query, string $result, User $user)
    {
        if ($result === 'all') return;

        $isWin = $result === 'win';
        $winnerCondition = $isWin ? 'affirmative' : 'negative';
        $loserCondition = $isWin ? 'negative' : 'affirmative';

        $query->where(function ($q) use ($user, $winnerCondition, $loserCondition, $isWin) {
            $q->where(function ($subQ) use ($user, $winnerCondition) {
                $subQ->whereHas('evaluations', fn($qe) => $qe->where('winner', $winnerCondition))
                    ->where('affirmative_user_id', $user->id);
            })->orWhere(function ($subQ) use ($user, $loserCondition) {
                $subQ->whereHas('evaluations', fn($qe) => $qe->where('winner', $loserCondition))
                    ->where('negative_user_id', $user->id);
            });
        });
    }

    /**
     * ゲストユーザー用結果フィルターを適用
     */
    private function applyGuestResultFilter($query, string $result, User $user, $demoUserIds)
    {
        if ($result === 'all') return;

        $isWin = $result === 'win';
        $winnerCondition = $isWin ? 'affirmative' : 'negative';
        $loserCondition = $isWin ? 'negative' : 'affirmative';

        $query->where(function ($q) use ($user, $demoUserIds, $winnerCondition, $loserCondition) {
            // 自分のディベート結果
            $q->where(function ($subQ) use ($user, $winnerCondition, $loserCondition) {
                $subQ->where(function ($winQ) use ($user, $winnerCondition) {
                    $winQ->whereHas('evaluations', fn($qe) => $qe->where('winner', $winnerCondition))
                        ->where('affirmative_user_id', $user->id);
                })->orWhere(function ($winQ) use ($user, $loserCondition) {
                    $winQ->whereHas('evaluations', fn($qe) => $qe->where('winner', $loserCondition))
                        ->where('negative_user_id', $user->id);
                });
            })
                // デモディベート結果
                ->orWhere(function ($subQ) use ($demoUserIds, $winnerCondition, $loserCondition) {
                    $subQ->where(function ($winQ) use ($demoUserIds, $winnerCondition) {
                        $winQ->whereHas('evaluations', fn($qe) => $qe->where('winner', $winnerCondition))
                            ->whereIn('affirmative_user_id', $demoUserIds);
                    })->orWhere(function ($winQ) use ($demoUserIds, $loserCondition) {
                        $winQ->whereHas('evaluations', fn($qe) => $qe->where('winner', $loserCondition))
                            ->whereIn('negative_user_id', $demoUserIds);
                    });
                });
        });
    }

    /**
     * サイドフィルターを適用
     */
    private function applySideFilter($query, string $side, User $user)
    {
        if ($side === 'all') return;

        if ($side === 'affirmative') {
            $query->where('affirmative_user_id', $user->id);
        } else {
            $query->where('negative_user_id', $user->id);
        }
    }

    /**
     * ゲストユーザー用サイドフィルターを適用
     */
    private function applyGuestSideFilter($query, string $side, User $user, $demoUserIds)
    {
        if ($side === 'all') return;

        $userColumn = $side === 'affirmative' ? 'affirmative_user_id' : 'negative_user_id';

        $query->where(function ($q) use ($user, $demoUserIds, $userColumn) {
            $q->where($userColumn, $user->id)
                ->orWhereIn($userColumn, $demoUserIds);
        });
    }

    /**
     * キーワードフィルターを適用
     */
    private function applyKeywordFilter($query, ?string $keyword)
    {
        if (!$keyword) return;

        $query->whereHas('room', fn($q) => $q->where('topic', 'like', '%' . $keyword . '%'));
    }

    /**
     * ソートを適用
     */
    private function applySorting($query, string $sort)
    {
        $direction = $sort === 'newest' ? 'desc' : 'asc';
        $query->orderBy('created_at', $direction);
    }

    /**
     * 統計情報を計算
     */
    private function calculateStats($query, User $user): array
    {
        $totalDebates = (clone $query)->count();
        $wins = $this->countWins(clone $query, $user);
        $losses = $this->countLosses(clone $query, $user);
        $winRate = $totalDebates > 0 ? round(($wins / $totalDebates) * 100) : 0;

        return [
            'total' => $totalDebates,
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $winRate
        ];
    }

    /**
     * 勝利数をカウント
     */
    private function countWins($query, User $user): int
    {
        return $query->where(function ($q) use ($user) {
            $q->where(function ($subQ) use ($user) {
                $subQ->whereHas('evaluations', fn($qe) => $qe->where('winner', 'affirmative'))
                    ->where('affirmative_user_id', $user->id);
            })->orWhere(function ($subQ) use ($user) {
                $subQ->whereHas('evaluations', fn($qe) => $qe->where('winner', 'negative'))
                    ->where('negative_user_id', $user->id);
            });
        })->count();
    }

    /**
     * 敗北数をカウント
     */
    private function countLosses($query, User $user): int
    {
        return $query->where(function ($q) use ($user) {
            $q->where(function ($subQ) use ($user) {
                $subQ->whereHas('evaluations', fn($qe) => $qe->where('winner', 'negative'))
                    ->where('affirmative_user_id', $user->id);
            })->orWhere(function ($subQ) use ($user) {
                $subQ->whereHas('evaluations', fn($qe) => $qe->where('winner', 'affirmative'))
                    ->where('negative_user_id', $user->id);
            });
        })->count();
    }

    /**
     * ゲストユーザーの統計情報を計算
     */
    private function calculateGuestStats(User $user, $demoUserIds): array
    {
        $baseQuery = $this->buildGuestQuery($user, $demoUserIds);

        $totalDebates = (clone $baseQuery)->count();
        $wins = $this->countGuestWins(clone $baseQuery, $user, $demoUserIds);
        $losses = $this->countGuestLosses(clone $baseQuery, $user, $demoUserIds);
        $winRate = $totalDebates > 0 ? round(($wins / $totalDebates) * 100) : 0;

        return [
            'total' => $totalDebates,
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $winRate
        ];
    }

    /**
     * ゲストユーザーの勝利数をカウント
     */
    private function countGuestWins($query, User $user, $demoUserIds): int
    {
        return $query->where(function ($q) use ($user, $demoUserIds) {
            // 自分の勝利
            $q->where(function ($subQ) use ($user) {
                $subQ->where(function ($winQ) use ($user) {
                    $winQ->whereHas('evaluations', fn($qe) => $qe->where('winner', 'affirmative'))
                        ->where('affirmative_user_id', $user->id);
                })->orWhere(function ($winQ) use ($user) {
                    $winQ->whereHas('evaluations', fn($qe) => $qe->where('winner', 'negative'))
                        ->where('negative_user_id', $user->id);
                });
            })
                // デモユーザーの勝利
                ->orWhere(function ($subQ) use ($demoUserIds) {
                    $subQ->where(function ($winQ) use ($demoUserIds) {
                        $winQ->whereHas('evaluations', fn($qe) => $qe->where('winner', 'affirmative'))
                            ->whereIn('affirmative_user_id', $demoUserIds);
                    })->orWhere(function ($winQ) use ($demoUserIds) {
                        $winQ->whereHas('evaluations', fn($qe) => $qe->where('winner', 'negative'))
                            ->whereIn('negative_user_id', $demoUserIds);
                    });
                });
        })->count();
    }

    /**
     * ゲストユーザーの敗北数をカウント
     */
    private function countGuestLosses($query, User $user, $demoUserIds): int
    {
        return $query->where(function ($q) use ($user, $demoUserIds) {
            // 自分の敗北
            $q->where(function ($subQ) use ($user) {
                $subQ->where(function ($loseQ) use ($user) {
                    $loseQ->whereHas('evaluations', fn($qe) => $qe->where('winner', 'negative'))
                        ->where('affirmative_user_id', $user->id);
                })->orWhere(function ($loseQ) use ($user) {
                    $loseQ->whereHas('evaluations', fn($qe) => $qe->where('winner', 'affirmative'))
                        ->where('negative_user_id', $user->id);
                });
            })
                // デモユーザーの敗北
                ->orWhere(function ($subQ) use ($demoUserIds) {
                    $subQ->where(function ($loseQ) use ($demoUserIds) {
                        $loseQ->whereHas('evaluations', fn($qe) => $qe->where('winner', 'negative'))
                            ->whereIn('affirmative_user_id', $demoUserIds);
                    })->orWhere(function ($loseQ) use ($demoUserIds) {
                        $loseQ->whereHas('evaluations', fn($qe) => $qe->where('winner', 'affirmative'))
                            ->whereIn('negative_user_id', $demoUserIds);
                    });
                });
        })->count();
    }

    /**
     * デモディベートのみかどうかを判定
     */
    private function isOnlyDemoDebates($debates, User $user): bool
    {
        if ($debates->total() === 0) {
            return false;
        }

        foreach ($debates as $debate) {
            if ($debate->affirmative_user_id === $user->id || $debate->negative_user_id === $user->id) {
                return false;
            }
        }

        return true;
    }

    /**
     * 特定のディベート詳細を表示する
     */
    public function show(Debate $debate)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$this->canAccessDebate($debate, $user)) {
            return redirect()->back();
        }

        $debate->load(['room.creator', 'affirmativeUser', 'negativeUser', 'messages.user', 'evaluations']);
        $evaluations = $debate->evaluations;

        return view('records.show', compact('debate', 'evaluations'));
    }

    /**
     * ディベートにアクセス可能かチェック
     */
    private function canAccessDebate(Debate $debate, User $user): bool
    {
        // 自分が参加したディベートは常にアクセス可能
        if ($debate->affirmative_user_id === $user->id || $debate->negative_user_id === $user->id) {
            return true;
        }

        // ゲストユーザーの場合はデモディベートにもアクセス可能
        if ($user->isGuest()) {
            $demoUserIds = $this->getDemoUserIds();
            return $demoUserIds->contains($debate->affirmative_user_id) ||
                $demoUserIds->contains($debate->negative_user_id);
        }

        return false;
    }
}
