<div class="flex items-center justify-between bg-gray-50 rounded-lg p-4">
    <span class="font-semibold text-gray-700">ステータス:</span>
    <span
        class="px-3 py-1 rounded-full text-sm font-medium {{ $room->status === 'waiting' ? 'bg-gray-100 text-gray-800' : ($room->status === 'ready' ? 'bg-yellow-100 text-yellow-800' : ($room->status === 'debating' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800')) }}">
        @if ($room->status === 'waiting')
            待機中
        @elseif ($room->status === 'ready')
            準備中
        @elseif ($room->status === 'debating')
            ディベート中
        @else
            終了
        @endif
    </span>
</div>
