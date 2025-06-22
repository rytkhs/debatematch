<!-- フィルターとソート -->
<div class="mb-6">
    <form id="filterForm" action="{{ route('records.index') }}" method="GET" class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-4" data-records-filter>
        <!-- モバイル用：縦並びレイアウト -->
        <div class="block lg:hidden space-y-3" data-filter-container="mobile">
            <!-- 上段：立場と結果 -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('rooms.side') }}</label>
                    <select name="side" class="w-full text-sm border border-gray-300 rounded-md px-2 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" data-filter-autosubmit>
                        <option value="all" {{ $side == 'all' ? 'selected' : '' }}>{{ __('records.all_sides') }}</option>
                        <option value="affirmative" {{ $side == 'affirmative' ? 'selected' : '' }}>{{ __('rooms.affirmative_side') }}</option>
                        <option value="negative" {{ $side == 'negative' ? 'selected' : '' }}>{{ __('rooms.negative_side') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('records.result') }}</label>
                    <select name="result" class="w-full text-sm border border-gray-300 rounded-md px-2 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" data-filter-autosubmit>
                        <option value="all" {{ $result == 'all' ? 'selected' : '' }}>{{ __('records.all_results') }}</option>
                        <option value="win" {{ $result == 'win' ? 'selected' : '' }}>{{ __('records.win') }}</option>
                        <option value="lose" {{ $result == 'lose' ? 'selected' : '' }}>{{ __('records.loss') }}</option>
                    </select>
                </div>
            </div>

            <!-- 中段：ソート -->
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('records.sort_order') }}</label>
                <select name="sort" class="w-full text-sm border border-gray-300 rounded-md px-2 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" data-filter-autosubmit>
                    <option value="newest" {{ $sort == 'newest' ? 'selected' : '' }}>{{ __('records.newest_first') }}</option>
                    <option value="oldest" {{ $sort == 'oldest' ? 'selected' : '' }}>{{ __('records.oldest_first') }}</option>
                </select>
            </div>

            <!-- 下段：検索 -->
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('records.keyword_search') }}</label>
                <input type="text" name="keyword" value="{{ $keyword }}" placeholder="{{ __('records.search_topic_placeholder') }}"
                    class="w-full text-sm border border-gray-300 rounded-md px-2 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" data-filter-autosubmit>
            </div>

            <!-- ボタン -->
            <div class="flex gap-2 pt-1">
                <button type="button" class="flex-1 px-3 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors" data-filter-reset>
                    {{ __('records.reset_filters') }}
                </button>
                <button type="submit" class="flex-1 px-3 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors" data-filter-submit>
                    {{ __('records.apply_filters') }}
                </button>
            </div>
        </div>

        <!-- デスクトップ用：横並びレイアウト -->
        <div class="hidden lg:flex gap-4 items-end" data-filter-container="desktop">
            <!-- フィルター項目 -->
            <div class="flex gap-3 flex-1">
                <!-- 立場フィルター -->
                <div class="min-w-0 flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('rooms.side') }}</label>
                    <select name="side" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" data-filter-autosubmit>
                        <option value="all" {{ $side == 'all' ? 'selected' : '' }}>{{ __('records.all_sides') }}</option>
                        <option value="affirmative" {{ $side == 'affirmative' ? 'selected' : '' }}>{{ __('rooms.affirmative_side') }}</option>
                        <option value="negative" {{ $side == 'negative' ? 'selected' : '' }}>{{ __('rooms.negative_side') }}</option>
                    </select>
                </div>

                <!-- 結果フィルター -->
                <div class="min-w-0 flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('records.result') }}</label>
                    <select name="result" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" data-filter-autosubmit>
                        <option value="all" {{ $result == 'all' ? 'selected' : '' }}>{{ __('records.all_results') }}</option>
                        <option value="win" {{ $result == 'win' ? 'selected' : '' }}>{{ __('records.win') }}</option>
                        <option value="lose" {{ $result == 'lose' ? 'selected' : '' }}>{{ __('records.loss') }}</option>
                    </select>
                </div>

                <!-- ソート -->
                <div class="min-w-0 flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('records.sort_order') }}</label>
                    <select name="sort" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" data-filter-autosubmit>
                        <option value="newest" {{ $sort == 'newest' ? 'selected' : '' }}>{{ __('records.newest_first') }}</option>
                        <option value="oldest" {{ $sort == 'oldest' ? 'selected' : '' }}>{{ __('records.oldest_first') }}</option>
                    </select>
                </div>

                <!-- キーワード検索 -->
                <div class="min-w-0 flex-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('records.keyword_search') }}</label>
                    <input type="text" name="keyword" value="{{ $keyword }}" placeholder="{{ __('records.search_topic_placeholder') }}"
                        class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" data-filter-autosubmit>
                </div>
            </div>

            <!-- ボタン -->
            <div class="flex gap-2">
                <button type="button" class="px-3 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors" data-filter-reset>
                    {{ __('records.reset_filters') }}
                </button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors" data-filter-submit>
                    {{ __('records.apply_filters') }}
                </button>
            </div>
        </div>
    </form>
</div>
