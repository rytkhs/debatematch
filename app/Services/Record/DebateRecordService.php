<?php

namespace App\Services\Record;

use App\Models\Debate;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DebateRecordService
{
    public function __construct(
        private FilterManager $filterManager
    ) {}

    /**
     * 通常ユーザーのディベート記録を取得
     */
    public function getDebatesForUser(User $user, array $filters): array
    {
        $query = $this->filterManager->buildBaseQuery($user);
        $this->filterManager->applyFilters($query, $filters, $user);
        $this->filterManager->applySorting($query, $filters['sort'] ?? 'newest');

        $debates = $query->paginate(6)->appends($filters);

        return [
            'debates' => $debates,
            'side' => $filters['side'] ?? 'all',
            'result' => $filters['result'] ?? 'all',
            'sort' => $filters['sort'] ?? 'newest',
            'keyword' => $filters['keyword'] ?? null,
        ];
    }

    /**
     * ゲストユーザーのディベート記録を取得
     */
    public function getDebatesForGuest(User $user, array $filters): array
    {
        $demoUserIds = $this->filterManager->getDemoUserIds();

        if ($demoUserIds->isEmpty()) {
            return $this->getDebatesForUser($user, $filters);
        }

        $query = $this->filterManager->buildGuestQuery($user, $demoUserIds);
        $this->filterManager->applyGuestFilters($query, $filters, $user, $demoUserIds);
        $this->filterManager->applySorting($query, $filters['sort'] ?? 'newest');

        $debates = $query->paginate(6)->appends($filters);
        $isDemo = $this->filterManager->isOnlyDemoDebates($debates, $user);

        return [
            'debates' => $debates,
            'isDemo' => $isDemo,
            'side' => $filters['side'] ?? 'all',
            'result' => $filters['result'] ?? 'all',
            'sort' => $filters['sort'] ?? 'newest',
            'keyword' => $filters['keyword'] ?? null,
        ];
    }

    /**
     * ディベートへのアクセス権限チェック
     */
    public function canAccessDebate(Debate $debate, User $user): bool
    {
        // ディベートの参加者かどうかをチェック
        if (
            $debate->affirmative_user_id === $user->id ||
            $debate->negative_user_id === $user->id
        ) {
            return true;
        }

        // ゲストユーザーの場合、デモディベートへのアクセスを許可
        if ($user->isGuest()) {
            $demoUserIds = $this->filterManager->getDemoUserIds();
            return $demoUserIds->contains($debate->affirmative_user_id) ||
                $demoUserIds->contains($debate->negative_user_id);
        }

        return false;
    }

    /**
     * リクエストからフィルター情報を取得
     */
    public function extractFilters(array $requestData): array
    {
        return [
            'side' => $requestData['side'] ?? 'all',
            'result' => $requestData['result'] ?? 'all',
            'sort' => $requestData['sort'] ?? 'newest',
            'keyword' => $requestData['keyword'] ?? null
        ];
    }
}
