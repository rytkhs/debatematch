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
    $isWinner = ($debate->evaluations->winner === 'affirmative' && $isAffirmative) ||
               ($debate->evaluations->winner === 'negative' && !$isAffirmative);

    $resultClass = $isWinner
        ? 'bg-success-light text-success border-success/30'
        : 'bg-danger-light text-danger border-danger/30';
    $resultText = $isWinner ? __('messages.win') : __('messages.loss');
    $resultIcon = $isWinner
        ? '<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';

    $side = $isAffirmative ? __('messages.affirmative_side') : __('messages.negative_side');
    $sideClass = $isAffirmative ? 'text-success' : 'text-danger';
    $opponent = $isAffirmative ? ($debate->negativeUser ? $debate->negativeUser->name : 'unknown') : ($debate->affirmativeUser ? $debate->affirmativeUser->name : 'unknown');
@endphp

@if($viewType === 'grid')
    @include('records.partials.debate-card-grid', compact('debate', 'isAffirmative', 'isWinner', 'resultClass', 'resultText', 'resultIcon', 'side', 'sideClass', 'opponent'))
@else
    @include('records.partials.debate-card-list', compact('debate', 'isAffirmative', 'isWinner', 'resultClass', 'resultText', 'side', 'sideClass', 'opponent'))
@endif
