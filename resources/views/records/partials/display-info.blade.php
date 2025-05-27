<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 sm:mb-4">
    <p class="text-xs sm:text-sm text-gray-600 mb-2 sm:mb-0">
        {{ __('messages.records_count', ['first' => $debates->firstItem() ?? 0, 'last' => $debates->lastItem() ?? 0, 'total' => $debates->total()]) }}
        @if($keyword || $side != 'all' || $result != 'all')
            <span class="font-semibold">{{ __('messages.filter_applied_indicator') }}</span>
        @endif
    </p>
    <div class="hidden md:flex items-center">
        <span class="mr-2 text-xs sm:text-sm text-gray-600">{{ __('messages.view_format_label') }}</span>
        <button id="viewGrid" class="p-1.5 sm:p-2 text-gray-600 hover:text-primary focus:outline-none view-button active">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
        </button>
        <button id="viewList" class="p-1.5 sm:p-2 text-gray-600 hover:text-primary focus:outline-none view-button">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
</div>
