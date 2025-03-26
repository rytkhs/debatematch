@php
    $statusStyles = [
        'waiting' => ['bg-yellow-100', 'text-yellow-800'],
        'ready' => ['bg-green-100', 'text-green-800'],
        'debating' => ['bg-blue-100', 'text-blue-800'],
        'finished' => ['bg-gray-100', 'text-gray-800'],
        'deleted' => ['bg-red-100', 'text-red-800']
    ];

    $statusLabels = [
        'waiting' => '募集中',
        'ready' => '準備完了',
        'debating' => 'ディベート中',
        'finished' => '終了',
        'deleted' => '閉鎖'
    ];
@endphp

<div class="flex items-center rounded-lg p-1">
    <span class="px-3 py-1 rounded-full text-md font-medium {{ implode(' ', $statusStyles[$room->status] ?? $statusStyles['finished']) }}">
        {{ $statusLabels[$room->status] ?? '不明' }}
    </span>
</div>
