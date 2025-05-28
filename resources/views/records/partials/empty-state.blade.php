<div class="col-span-full">
    <div class="text-center py-16 px-6">
        <div class="max-w-md mx-auto">
            <!-- イラストレーション -->
            <div class="mb-6">
                <svg class="mx-auto h-24 w-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>

            <!-- メッセージ -->
            <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ __('messages.no_debate_records') }}</h3>
            <p class="text-gray-600 mb-8 leading-relaxed">
                {{ __('messages.no_debate_records_description') }}
            </p>

            <!-- アクションボタン -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('rooms.index') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    {{ __('messages.join_debate') }}
                </a>
                <a href="{{ route('guide') }}" class="inline-flex items-center px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg border border-gray-300 shadow-sm transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('messages.how_to_use') }}
                </a>
            </div>
        </div>
    </div>
</div>
