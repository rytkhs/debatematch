<div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-all duration-200">
    <!-- カードヘッダー -->
    <div class="p-3 sm:p-4 border-b border-gray-100">
        <!-- 状態と日付 -->
        <div class="flex justify-between items-center mb-2 sm:mb-3">
            <span class="inline-flex items-center px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full text-xs font-medium border {{ $resultClass }}">
                {!! $resultIcon !!}{{ $resultText }}
            </span>
            <span class="text-xs text-gray-500">
                {{ App::getLocale() === 'ja' ? $debate->created_at->format('Y/m/d') : $debate->created_at->format('M d, Y') }}
            </span>
        </div>

        <!-- 論題 -->
        <h3 class="text-sm sm:text-base font-medium text-gray-900 mb-1 line-clamp-2">{{ $debate->room->topic }}</h3>
        <p class="text-xs text-gray-500">Room: {{ $debate->room->name }}</p>
    </div>

    <!-- カード情報 -->
    <div class="p-3 sm:p-4 bg-gray-50 flex-grow">
        <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-2 sm:mb-3">
            <div>
                <p class="text-xs text-gray-500 mb-0.5 sm:mb-1">{{ __('messages.your_side') }}</p>
                <p class="text-xs sm:text-sm font-medium {{ $sideClass }}">{{ $side }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-0.5 sm:mb-1">{{ __('messages.opponent') }}</p>
                <p class="text-xs sm:text-sm font-medium">{{ $opponent }}</p>
            </div>
        </div>

        @if($debate->evaluations)
            <div class="text-xs text-gray-600 mb-0.5 sm:mb-1">
                <span class="font-medium">{{ __('messages.evaluation_label') }}</span>
            </div>
            <p class="text-xs text-gray-600 line-clamp-2">
                {{ Str::limit($isAffirmative ? $debate->evaluations->feedback_for_affirmative : $debate->evaluations->feedback_for_negative, 80) }}
            </p>
        @endif
    </div>

    <!-- カードフッター -->
    <div class="p-2 sm:p-3 bg-gray-50 border-t border-gray-100">
        <a href="{{ route('records.show', $debate) }}" class="w-full inline-flex justify-center items-center px-3 sm:px-4 py-1.5 sm:py-2 border border-primary rounded-md shadow-sm text-xs sm:text-sm font-medium text-primary bg-white hover:bg-primary hover:text-white transition-colors duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            {{ __('messages.view_details') }}
        </a>
    </div>
</div>
