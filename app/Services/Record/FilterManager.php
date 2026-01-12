<?php

namespace App\Services\Record;

use App\Models\Debate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FilterManager
{
    /**
     * 通常ユーザー用の基本クエリを構築
     */
    public function buildBaseQuery(User $user): Builder
    {
        return Debate::with(['room', 'affirmativeUser', 'negativeUser', 'debateEvaluation'])
            ->whereHas('room', fn($q) => $q->where('status', 'finished'))
            ->where(fn($q) => $q->where('affirmative_user_id', $user->id)
                ->orWhere('negative_user_id', $user->id))
            ->whereHas('debateEvaluation');
    }

    /**
     * ゲストユーザー用のクエリを構築
     */
    public function buildGuestQuery(User $user, Collection $demoUserIds): Builder
    {
        return Debate::with(['room', 'affirmativeUser', 'negativeUser', 'debateEvaluation'])
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
            ->whereHas('debateEvaluation');
    }

    /**
     * 通常ユーザー用フィルターを適用
     */
    public function applyFilters(Builder $query, array $filters, User $user): Builder
    {
        $this->applyResultFilter($query, $filters['result'] ?? 'all', $user);
        $this->applySideFilter($query, $filters['side'] ?? 'all', $user);
        $this->applyKeywordFilter($query, $filters['keyword'] ?? null);

        return $query;
    }

    /**
     * ゲストユーザー用フィルターを適用
     */
    public function applyGuestFilters(Builder $query, array $filters, User $user, Collection $demoUserIds): Builder
    {
        // サイドや結果フィルターが適用されているかどうかを判定
        // キーワードフィルターはデモディベートにも適用可能なので除外
        $hasSideOrResultFilters = ($filters['result'] ?? 'all') !== 'all' ||
            ($filters['side'] ?? 'all') !== 'all';

        if ($hasSideOrResultFilters) {
            // サイドまたは結果フィルターが適用されている場合は、ゲストユーザーが実際に参加したディベートのみを対象とする
            $this->applyFilteredGuestQuery($query, $filters, $user);
        } else {
            // サイドや結果フィルターが適用されていない場合は、従来通りデモディベートも含める
            $this->applyGuestResultFilter($query, $filters['result'] ?? 'all', $user, $demoUserIds);
            $this->applyGuestSideFilter($query, $filters['side'] ?? 'all', $user, $demoUserIds);
            // キーワードフィルターも適用
            $this->applyKeywordFilter($query, $filters['keyword'] ?? null);
        }

        return $query;
    }

    /**
     * フィルターが適用されている場合のゲストユーザークエリ
     */
    private function applyFilteredGuestQuery(Builder $query, array $filters, User $user): void
    {
        // ゲストユーザーが実際に参加したディベートのみを対象にベースクエリを変更
        $query->where(function ($q) use ($user) {
            $q->where('affirmative_user_id', $user->id)
                ->orWhere('negative_user_id', $user->id);
        });

        // 通常ユーザー用のフィルターを適用
        $this->applyResultFilter($query, $filters['result'] ?? 'all', $user);
        $this->applySideFilter($query, $filters['side'] ?? 'all', $user);
        $this->applyKeywordFilter($query, $filters['keyword'] ?? null);
    }

    /**
     * ソートを適用
     */
    public function applySorting(Builder $query, string $sort): Builder
    {
        $direction = $sort === 'newest' ? 'desc' : 'asc';
        $query->orderBy('created_at', $direction);

        return $query;
    }

    /**
     * デモユーザーのIDを取得
     */
    public function getDemoUserIds(): Collection
    {
        return User::whereIn('email', [
            'demo1@example.com',
            'demo2@example.com',
        ])->pluck('id');
    }

    /**
     * デモディベートのみかどうかを判定
     */
    public function isOnlyDemoDebates($debates, User $user): bool
    {
        foreach ($debates as $debate) {
            if (
                $debate->affirmative_user_id === $user->id ||
                $debate->negative_user_id === $user->id
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * 結果フィルターを適用（通常ユーザー用）
     */
    private function applyResultFilter(Builder $query, string $result, User $user): void
    {
        if ($result === 'all') return;

        $isWin = $result === 'win';
        $winnerCondition = $isWin ? 'affirmative' : 'negative';
        $loserCondition = $isWin ? 'negative' : 'affirmative';

        $query->where(function ($q) use ($user, $winnerCondition, $loserCondition) {
            $q->where(function ($subQ) use ($user, $winnerCondition) {
                $subQ->whereHas('debateEvaluation', fn($qe) => $qe->where('winner', $winnerCondition)->whereNotNull('winner'))
                    ->where('affirmative_user_id', $user->id);
            })->orWhere(function ($subQ) use ($user, $loserCondition) {
                $subQ->whereHas('debateEvaluation', fn($qe) => $qe->where('winner', $loserCondition)->whereNotNull('winner'))
                    ->where('negative_user_id', $user->id);
            });
        });
    }

    /**
     * 結果フィルターを適用（ゲストユーザー用）
     */
    private function applyGuestResultFilter(Builder $query, string $result, User $user, Collection $demoUserIds): void
    {
        if ($result === 'all') return;

        $isWin = $result === 'win';
        $winnerCondition = $isWin ? 'affirmative' : 'negative';
        $loserCondition = $isWin ? 'negative' : 'affirmative';

        $query->where(function ($q) use ($user, $demoUserIds, $winnerCondition, $loserCondition) {
            // 自分のディベート結果
            $q->where(function ($subQ) use ($user, $winnerCondition, $loserCondition) {
                $subQ->where(function ($winQ) use ($user, $winnerCondition) {
                    $winQ->whereHas('debateEvaluation', fn($qe) => $qe->where('winner', $winnerCondition)->whereNotNull('winner'))
                        ->where('affirmative_user_id', $user->id);
                })->orWhere(function ($winQ) use ($user, $loserCondition) {
                    $winQ->whereHas('debateEvaluation', fn($qe) => $qe->where('winner', $loserCondition)->whereNotNull('winner'))
                        ->where('negative_user_id', $user->id);
                });
            })
                // デモディベート結果
                ->orWhere(function ($subQ) use ($demoUserIds, $winnerCondition, $loserCondition) {
                    $subQ->where(function ($winQ) use ($demoUserIds, $winnerCondition) {
                        $winQ->whereHas('debateEvaluation', fn($qe) => $qe->where('winner', $winnerCondition)->whereNotNull('winner'))
                            ->whereIn('affirmative_user_id', $demoUserIds);
                    })->orWhere(function ($winQ) use ($demoUserIds, $loserCondition) {
                        $winQ->whereHas('debateEvaluation', fn($qe) => $qe->where('winner', $loserCondition)->whereNotNull('winner'))
                            ->whereIn('negative_user_id', $demoUserIds);
                    });
                });
        });
    }

    /**
     * サイドフィルターを適用（通常ユーザー用）
     */
    private function applySideFilter(Builder $query, string $side, User $user): void
    {
        if ($side === 'all') return;

        if ($side === 'affirmative') {
            $query->where('affirmative_user_id', $user->id);
        } else {
            $query->where('negative_user_id', $user->id);
        }
    }

    /**
     * サイドフィルターを適用（ゲストユーザー用）
     */
    private function applyGuestSideFilter(Builder $query, string $side, User $user, Collection $demoUserIds): void
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
    private function applyKeywordFilter(Builder $query, ?string $keyword): void
    {
        if (!$keyword) return;

        $query->whereHas('room', fn($q) => $q->where('topic', 'like', '%' . $keyword . '%'));
    }
}
