<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-6 sm:py-8">
            <!-- ディベート情報&対戦カードセクション -->
            <div class="bg-white rounded-xl shadow-sm mb-6 sm:mb-8">
                <div class="p-4 sm:p-6">
                    <!-- ディベートトピックと基本情報 -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 mb-4 sm:mb-6">
                        <div>
                            <div>
                                <!-- トピック情報 -->
                                <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-2">{{ $debate->room->topic }}</h2>
                                <p class="text-xs sm:text-sm text-gray-600 mb-1 sm:mb-2">
                                    <span class="inline-flex items-center">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                        {{ __('records.room_label') }} {{ $debate->room->name }}
                                    </span>
                                    <span class="inline-flex items-center ml-3 sm:ml-4">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        {{ __('records.host_label') }} {{ $debate->room->creator->name ?? 'unknown' }}
                                    </span>
                                </p>
                                <p class="text-xs sm:text-sm text-gray-600">
                                    <span class="inline-flex items-center">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ App::getLocale() === 'ja' ? $debate->created_at->format('Y年m月d日 H:i') : $debate->created_at->format('M d, Y H:i') }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 対戦カード -->
                    <div class="flex flex-col md:flex-row gap-4 sm:gap-6 mb-4 sm:mb-6 relative">
                        <!-- 中央の対戦VS表示 -->
                        <div class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 z-10 hidden md:flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 bg-primary text-white rounded-full font-bold text-base sm:text-lg">VS</div>

                        <!-- 肯定側 -->
                        <div class="p-3 sm:p-5 rounded-xl flex-1 {{ $debate->affirmative_user_id === Auth::id() ? 'bg-primary-light border-l-4 border-primary' : 'bg-gray-100' }} relative overflow-hidden">
                            @if($evaluations && $evaluations->winner && $evaluations->winner === 'affirmative')
                                <div class="absolute top-0 right-0 w-14 h-14 sm:w-16 sm:h-16">
                                    <div class="absolute transform rotate-45 bg-success text-white text-xs font-bold py-1 right-[-35px] top-[10px] w-[140px] text-center">{{ __('records.winner_label') }}</div>
                                </div>
                            @endif
                            <h3 class="text-base sm:text-lg font-semibold text-primary mb-3 sm:mb-4 flex items-center">
                                {{ __('debates_ui.affirmative_side_label') }}
                            </h3>
                            <div class="flex items-start gap-3 sm:gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm sm:text-lg font-semibold">
                                        {{ mb_substr($debate->affirmativeUser->name ?? 'unknown', 0, 1) }}
                                    </div>
                                </div>
                                <div>
                                    <p class="font-medium text-sm sm:text-base text-gray-900">
                                        {{ $debate->affirmativeUser->name ?? 'unknown' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 否定側 -->
                        <div class="p-3 sm:p-5 rounded-xl flex-1 {{ $debate->negative_user_id === Auth::id() ? 'bg-primary-light border-l-4 border-primary' : 'bg-gray-100' }} relative overflow-hidden">
                            @if($evaluations && $evaluations->winner && $evaluations->winner === 'negative')
                                <div class="absolute top-0 right-0 w-14 h-14 sm:w-16 sm:h-16">
                                    <div class="absolute transform rotate-45 bg-success text-white text-xs font-bold py-1 right-[-35px] top-[10px] w-[140px] text-center">{{ __('records.winner_label') }}</div>
                                </div>
                            @endif
                            <h3 class="text-base sm:text-lg font-semibold text-gray-700 mb-3 sm:mb-4 flex items-center">
                                {{ __('debates_ui.negative_side_label') }}
                            </h3>
                            <div class="flex items-start gap-3 sm:gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-sm sm:text-lg font-semibold">
                                        {{ mb_substr($debate->negativeUser->name ?? 'unknown', 0, 1) }}
                                    </div>
                                </div>
                                <div>
                                    <p class="font-medium text-sm sm:text-base text-gray-900">
                                        {{ $debate->negativeUser->name ?? 'unknown' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- タブナビゲーション -->
            <div class="mb-4 sm:mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-6 sm:space-x-8" aria-label="Tabs">
                        <button
                            class="tab-button whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-xs sm:text-sm transition-all duration-200 ease-in-out"
                            onclick="showTab('result')"
                            id="tab-result"
                            data-active="true"
                        >
                            <span class="material-icons align-middle mr-1 text-sm sm:text-base">analytics</span> {{ __('records.result_tab') }}
                        </button>
                        <button
                            class="tab-button whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-xs sm:text-sm transition-all duration-200 ease-in-out"
                            onclick="showTab('debate')"
                            id="tab-debate"
                            data-active="false"
                        >
                            <span class="material-icons align-middle mr-1 text-sm sm:text-base">chat</span> {{ __('records.debate_content_tab') }}
                        </button>
                    </nav>
                </div>
            </div>

            <!-- 結果タブコンテンツ -->
            <div id="content-result">
                <!-- 評価コンテンツ -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6 sm:mb-8">
                    <div class="p-4 sm:p-6">
                        @if($evaluations)
                            <div class="space-y-4 sm:space-y-6">
                                <!-- 論点の分析 -->
                                @if($evaluations->analysis)
                                <div class="relative">
                                    <div class="flex items-center mb-3 sm:mb-4">
                                        <span class="material-icons-outlined text-primary mr-2 text-sm sm:text-base">psychology</span>
                                        <h3 class="text-base sm:text-lg font-semibold text-gray-900">{{ __('records.analysis_of_points') }}</h3>
                                    </div>
                                    <div class="bg-white rounded-xl p-3 sm:p-5 border border-gray-200 shadow-sm">
                                        <div class="leading-relaxed text-sm sm:text-base text-gray-700 prose max-w-none">{!! Str::markdown($evaluations->analysis) !!}</div>
                                    </div>
                                </div>
                                @endif

                                <!-- 判定結果 -->
                                @if($evaluations->reason)
                                <div class="relative">
                                    <div class="flex items-center mb-3 sm:mb-4">
                                        <span class="material-icons-outlined text-primary mr-2 text-sm sm:text-base">gavel</span>
                                        <h3 class="text-base sm:text-lg font-semibold text-gray-900">{{ __('records.judgment_result') }}</h3>
                                    </div>
                                    <div class="bg-primary-light rounded-xl p-3 sm:p-5 border-l-4 border-primary">
                                        <div class="leading-relaxed text-sm sm:text-base text-gray-700 prose max-w-none">{!! Str::markdown($evaluations->reason) !!}</div>
                                        @if ($evaluations->winner)
                                        <div class="bg-white p-3 sm:p-4 rounded-lg mt-3 sm:mt-4 flex items-center justify-between shadow-sm">
                                            <p class="font-medium text-sm sm:text-base text-gray-700">{{ __('records.winner_is') }}</p>
                                            <p class="font-semibold text-sm sm:text-base {{ $evaluations->winner === 'affirmative' ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $evaluations->winner === 'affirmative' ? __('debates_ui.affirmative_side_label') : __('debates_ui.negative_side_label') }}
                                            </p>
                                        </div>
                                        @else
                                        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg mt-3 sm:mt-4 flex items-center shadow-sm border border-gray-200">
                                            <div class="flex items-center w-full">
                                                <span class="material-icons-outlined text-gray-500 mr-2 text-sm sm:text-base">help_outline</span>
                                                <p class="font-medium text-sm sm:text-base text-gray-600">{{ __('records.evaluation_inconclusive') }}</p>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <!-- フィードバック -->
                                @if($evaluations->feedback_for_affirmative || $evaluations->feedback_for_negative)
                                <div class="relative">
                                    <div class="flex items-center mb-3 sm:mb-4">
                                        <span class="material-icons-outlined text-primary mr-2 text-sm sm:text-base">feedback</span>
                                        <h3 class="text-base sm:text-lg font-semibold text-gray-900">{{ __('records.feedback') }}</h3>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                        <!-- 肯定側へのフィードバック -->
                                        @if($evaluations->feedback_for_affirmative)
                                        <div class="bg-white rounded-xl p-3 sm:p-5 border border-gray-200 shadow-sm {{ $debate->affirmative_user_id === Auth::id() ? 'border-l-4 border-l-primary' : '' }}">
                                            <h4 class="text-sm sm:text-base font-semibold {{ $debate->affirmative_user_id === Auth::id() ? 'text-primary' : 'text-gray-700' }} mb-2 sm:mb-3 flex items-center">
                                                <span class="material-icons-outlined mr-2 text-xs sm:text-sm">person</span>{{ __('records.feedback_for_affirmative') }}
                                            </h4>
                                            <div class="text-sm sm:text-base text-gray-700 prose max-w-none">{!! Str::markdown($evaluations->feedback_for_affirmative) !!}</div>
                                        </div>
                                        @endif

                                        <!-- 否定側へのフィードバック -->
                                        @if($evaluations->feedback_for_negative)
                                        <div class="bg-white rounded-xl p-3 sm:p-5 border border-gray-200 shadow-sm {{ $debate->negative_user_id === Auth::id() ? 'border-l-4 border-l-primary' : '' }}">
                                            <h4 class="text-sm sm:text-base font-semibold {{ $debate->negative_user_id === Auth::id() ? 'text-primary' : 'text-gray-700' }} mb-2 sm:mb-3 flex items-center">
                                                <span class="material-icons-outlined mr-2 text-xs sm:text-sm">person</span>{{ __('records.feedback_for_negative') }}
                                            </h4>
                                            <div class="text-sm sm:text-base text-gray-700 prose max-w-none">{!! Str::markdown($evaluations->feedback_for_negative) !!}</div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-xl p-4 sm:p-6 border border-gray-200 text-center">
                                <div class="flex items-center justify-center mb-3 sm:mb-4">
                                    <span class="material-icons-outlined text-gray-500 mr-2 text-sm sm:text-base">sentiment_dissatisfied</span>
                                    <h3 class="text-base sm:text-lg font-semibold text-gray-700">{{ __('records.no_evaluation_available') }}</h3>
                                </div>

                            </div>
                        @endif


                        <!-- アクションボタン -->
                        <div class="border-t border-gray-200 mt-6 sm:mt-8 pt-4 sm:pt-6 flex justify-end gap-3 sm:gap-4">
                            <a href="{{ route('records.index') }}" class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs sm:text-sm font-medium rounded-lg transition duration-150 ease-in-out">
                                <i class="fas fa-history mr-1 sm:mr-2"></i>
                                {{ __('records.view_debate_history') }}
                            </a>
                            <a href="{{ route('welcome') }}" class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 bg-primary hover:bg-primary-dark text-white text-xs sm:text-sm font-medium rounded-lg transition duration-150 ease-in-out">
                                <i class="fas fa-home mr-1 sm:mr-2"></i>
                                {{ __('errors.back_to_home') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ディベート内容タブ -->
            <div id="content-debate" class="hidden">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden h-screen flex flex-col">
                    <div class="px-2 sm:px-3 flex-1 flex flex-col min-h-0">
                        <livewire:debates.chat :debate="$debate" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>

    @vite(['resources/js/pages/records-show.js'])
</x-app-layout>
