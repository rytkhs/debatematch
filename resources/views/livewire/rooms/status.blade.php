@php
    $statusStyles = [
        'waiting' => ['bg-yellow-100', 'text-yellow-800'],
        'ready' => ['bg-green-100', 'text-green-800'],
        'debating' => ['bg-blue-100', 'text-blue-800'],
        'finished' => ['bg-gray-100', 'text-gray-800'],
        'deleted' => ['bg-red-100', 'text-red-800']
    ];

    // 翻訳キーとステータスのマッピング
    $statusTranslationKeys = [
        'waiting' => 'messages.waiting_status',
        'ready' => 'messages.ready_status',
        'debating' => 'messages.debate_in_progress', // 進行中のステータス用キー
        'finished' => 'messages.finished',
        'deleted' => 'messages.closed_status'
    ];

    // 翻訳キーを取得。存在しない場合は不明ステータス用キー
    $translationKey = $statusTranslationKeys[$room->status] ?? 'messages.unknown_status';
    // スタイルを取得。存在しない場合はfinishedのスタイル
    $styleClasses = $statusStyles[$room->status] ?? $statusStyles['finished'];
@endphp

<div class="flex items-center rounded-lg p-1">
    <span class="px-2 py-0.5 rounded-full text-sm font-medium {{ implode(' ', $styleClasses) }}">
        {{ __($translationKey) }}
    </span>
</div>
