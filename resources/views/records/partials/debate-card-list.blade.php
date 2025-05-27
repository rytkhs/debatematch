<div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-all duration-200">
    <div class="p-4 sm:p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 mb-3 sm:mb-4">
            <div>
                <div class="flex items-center gap-2 sm:gap-3 mb-2">
                    <span class="inline-flex items-center px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full text-xs font-medium border {{ $resultClass }}">
                        @if($isWinner)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        @endif
                        {{ $resultText }}
                    </span>
                    <span class="text-xs text-gray-500">{{ $debate->created_at->format('Y/m/d') }}</span>
                </div>
                <h2 class="text-base sm:text-lg font-medium text-gray-900 mb-1">{{ $debate->room->topic }}</h2>
            </div>
            <a href="{{ route('records.show', $debate) }}" class="sm:self-start inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 border border-primary rounded-md shadow-sm text-xs sm:text-sm font-medium text-primary bg-white hover:bg-primary hover:text-white transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                {{ __('messages.view_details') }}
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 text-xs sm:text-sm text-gray-600">
            <div>
                <p class="mb-1">
                    <span class="font-medium {{ $sideClass }}">{{ $side }}</span>
                </p>
                <p>vs. <span class="font-medium">{{ $opponent }}</span></p>
            </div>
            <div>
                <p class="mb-1">Room: <span class="font-medium">{{ $debate->room->name }}</span></p>
                <p>Host: <span class="font-medium">{{ $debate->room->creator->name ?? 'unknown' }}</span></p>
            </div>
            @if($debate->evaluations)
                <div>
                    <p class="mb-1">{{ __('messages.evaluation_label') }}:</p>
                    <p class="text-xs sm:text-sm line-clamp-2">{{ Str::limit($isAffirmative ? $debate->evaluations->feedback_for_affirmative : $debate->evaluations->feedback_for_negative, 100) }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
