<div class="mb-5 sm:mb-6">
    <form id="filterForm" action="{{ route('records.index') }}" method="GET" class="mb-5 sm:mb-6 flex flex-col sm:flex-row gap-3 sm:gap-4 items-start sm:items-center justify-between">
        <div class="flex flex-wrap items-center gap-2 sm:gap-4">
            <div class="relative">
                <select name="side" class="filter-select appearance-none bg-white border border-gray-300 rounded-lg px-3 sm:px-4 py-1.5 sm:py-2 pr-7 sm:pr-8 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-xs sm:text-sm">
                    <option value="all" {{ $side == 'all' ? 'selected' : '' }}>{{ __('messages.all_sides') }}</option>
                    <option value="affirmative" {{ $side == 'affirmative' ? 'selected' : '' }}>{{ __('messages.affirmative_side') }}</option>
                    <option value="negative" {{ $side == 'negative' ? 'selected' : '' }}>{{ __('messages.negative_side') }}</option>
                </select>
            </div>
            <div class="relative">
                <select name="result" class="filter-select appearance-none bg-white border border-gray-300 rounded-lg px-3 sm:px-4 py-1.5 sm:py-2 pr-7 sm:pr-8 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-xs sm:text-sm">
                    <option value="all" {{ $result == 'all' ? 'selected' : '' }}>{{ __('messages.all_results') }}</option>
                    <option value="win" {{ $result == 'win' ? 'selected' : '' }}>{{ __('messages.win') }}</option>
                    <option value="lose" {{ $result == 'lose' ? 'selected' : '' }}>{{ __('messages.loss') }}</option>
                </select>
            </div>
            <div class="relative">
                <select name="sort" class="filter-select appearance-none bg-white border border-gray-300 rounded-lg px-3 sm:px-4 py-1.5 sm:py-2 pr-7 sm:pr-8 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-xs sm:text-sm">
                    <option value="newest" {{ $sort == 'newest' ? 'selected' : '' }}>{{ __('messages.newest_first') }}</option>
                    <option value="oldest" {{ $sort == 'oldest' ? 'selected' : '' }}>{{ __('messages.oldest_first') }}</option>
                </select>
            </div>
            <div class="relative flex-1 max-w-xs">
                <input type="text" name="keyword" value="{{ $keyword }}" placeholder="{{ __('messages.search_topic_placeholder') }}"
                    class="filter-input w-full px-3 sm:px-4 py-1.5 sm:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-xs sm:text-sm">
            </div>
        </div>
        <div class="flex justify-end space-x-2">
            <button type="button" id="resetFilters" class="px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                {{ __('messages.reset_filters') }}
            </button>
            <button type="submit" class="px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-white bg-primary border border-transparent rounded-md shadow-sm hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                {{ __('messages.apply_filters') }}
            </button>
        </div>
    </form>
</div>
