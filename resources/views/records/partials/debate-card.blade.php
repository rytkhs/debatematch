@php
    // ディベートの種類を判定
    $isOwnDebate = $debate->affirmative_user_id === $currentUser->id || $debate->negative_user_id === $currentUser->id;
    $isDemoDebate = $isGuest && (in_array($debate->affirmative_user_id, $demoUserIds) || in_array($debate->negative_user_id, $demoUserIds));

    // 表示用のユーザー情報を決定
    if ($isOwnDebate) {
        $viewerUserId = $currentUser->id;
        $isAffirmative = $debate->affirmative_user_id === $currentUser->id;
        $debateType = 'own';
    } elseif ($isDemoDebate) {
        $demoUserId = in_array($debate->affirmative_user_id, $demoUserIds) ? $debate->affirmative_user_id : $debate->negative_user_id;
        $viewerUserId = $demoUserId;
        $isAffirmative = $debate->affirmative_user_id === $demoUserId;
        $debateType = 'demo';
    } else {
        return;
    }

    // 勝敗判定
    if (!$debate->debateEvaluation || !$debate->debateEvaluation->winner) {
        // 評価不能の場合
        $isWinner = null;
        $resultClass = 'bg-gray-100 text-gray-700 border-gray-300';
        $resultText = __('records.evaluation_inconclusive_short');
        $resultIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
    } else {
        // 通常の勝敗判定
        $isWinner = ($debate->debateEvaluation->winner === 'affirmative' && $isAffirmative) ||
                   ($debate->debateEvaluation->winner === 'negative' && !$isAffirmative);

        $resultClass = $isWinner
            ? 'bg-success-light text-success border-success/30'
            : 'bg-danger-light text-danger border-danger/30';
        $resultText = $isWinner ? __('records.win') : __('records.loss');
        $resultIcon = $isWinner
            ? '<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>'
            : '<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';
    }

    $side = $isAffirmative ? __('rooms.affirmative_side') : __('rooms.negative_side');
    $sideClass = $isAffirmative ? 'text-success' : 'text-danger';
    $opponent = $isAffirmative ? ($debate->negativeUser ? $debate->negativeUser->name : __('rooms.unknown_user')) : ($debate->affirmativeUser ? $debate->affirmativeUser->name : __('rooms.unknown_user'));
@endphp

@if($viewType === 'grid')
    @include('records.partials.debate-card-grid', compact('debate', 'isAffirmative', 'isWinner', 'resultClass', 'resultText', 'resultIcon', 'side', 'sideClass', 'opponent'))
@else
    @include('records.partials.debate-card-list', compact('debate', 'isAffirmative', 'isWinner', 'resultClass', 'resultText', 'side', 'sideClass', 'opponent'))
@endif
