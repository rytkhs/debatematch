<div class="group bg-white rounded-xl shadow-sm hover:shadow-md border border-gray-200 overflow-hidden transition-all duration-300">
    <div class="p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- 左側：メイン情報 -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 min-w-0">
                        <!-- 日付とルーム名 -->
                        <div class="flex items-center gap-3 mb-2">
                            <span class="inline-flex items-center text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-md">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ App::getLocale() === 'ja' ? $debate->created_at->format('Y/m/d') : $debate->created_at->format('M d, Y') }}
                            </span>
                            <span class="text-xs text-gray-600 flex items-center">
                                <svg class="w-3 h-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                {{ $debate->room->name }}
                            </span>
                        </div>

                        <!-- 論題 -->
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            {{ $debate->room->topic }}
                        </h3>
                    </div>

                    <!-- 結果バッジ -->
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $resultClass }} shadow-sm ml-4">
                        {!! $resultIcon !!}
                        <span class="ml-1">{{ $resultText }}</span>
                    </span>
                </div>

                <!-- 対戦情報 -->
                <div class="flex items-center gap-6 mb-3">
                    <div class="flex items-center">
                        <span class="text-xs text-gray-500 mr-2">{{ __('records.you') }}:</span>
                        <span class="inline-flex items-center text-sm font-medium {{ $sideClass }}">
                            {{ $side }}
                        </span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-xs text-gray-500 mr-2">{{ __('records.opponent') }}:</span>
                        <span class="text-sm font-medium text-gray-700 flex items-center">
                            <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ $opponent }}
                        </span>
                    </div>
                </div>

                <!-- 評価フィードバック -->
                @if($debate->evaluations)
                    <div class="bg-blue-50 rounded-lg p-3 border-l-4 border-blue-400">
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-blue-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            <div>
                                <p class="text-xs font-medium text-blue-800 mb-1">{{ __('records.ai_evaluation') }}</p>
                                <p class="text-sm text-blue-700 line-clamp-2">
                                    {{ Str::limit($isAffirmative ? $debate->evaluations->feedback_for_affirmative : $debate->evaluations->feedback_for_negative, 150) }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- 右側：アクションボタン -->
            <div class="lg:ml-6 flex-shrink-0">
                <a href="{{ route('records.show', $debate) }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    {{ __('rooms.view_details') }}
                </a>
            </div>
        </div>
    </div>
</div>
