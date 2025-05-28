<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center sm:gap-4">
        <!-- 表示件数情報 -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ __('messages.records_count', ['first' => $debates->firstItem() ?? 0, 'last' => $debates->lastItem() ?? 0, 'total' => $debates->total()]) }}
            </div>

            @if($keyword)
                <div class="flex items-center text-sm text-blue-600 bg-blue-50 px-2 py-1 rounded-full">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span class="text-xs sm:text-sm">{{ __('messages.searching_for', ['keyword' => $keyword]) }}</span>
                </div>
            @endif
        </div>

        <!-- フィルター状態表示 -->
        <div class="flex flex-wrap items-center gap-1 sm:gap-2">
            @if($side !== 'all')
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    {{ __('messages.side_filter', ['side' => $side === 'affirmative' ? __('messages.affirmative_side') : __('messages.negative_side')]) }}
                </span>
            @endif

            @if($result !== 'all')
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $result === 'win' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ __('messages.result_filter', ['result' => $result === 'win' ? __('messages.win') : __('messages.loss')]) }}
                </span>
            @endif

            @if($sort !== 'newest')
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    {{ $sort === 'oldest' ? __('messages.oldest_first') : __('messages.newest_first') }}
                </span>
            @endif
        </div>
    </div>
</div>
