<header class="debate-header bg-white border-b border-gray-200 shadow-sm z-10">
    <div class="mx-auto px-4 sm:px-6 lg:px-8 py-1">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <!-- 左側: トピック情報 -->
            <div class="mb-2 md:mb-0 flex items-center">
                <!-- デスクトップ用ハンバーガーメニュー -->
                <button id="desktop-hamburger-menu" class="hidden md:block text-gray-700 p-2 rounded-full hover:bg-gray-100">
                    <span class="material-icons">menu</span>
                </button>

                <!-- PC表示時のルーム名とトピック -->
                <div class="hidden lg:block ml-2">
                    <h1 class="text-sm md:text-lg font-medium text-gray-800 truncate">{{ $debate->room->name }}</h1>
                    <p class="text-md md:text-xl font-bold text-gray-900 mt-0 break-words">
                        {{ $debate->room->topic }}
                    </p>
                </div>
            </div>

            <!-- 右側: ターン情報とタイマー -->
            <div class="flex items-center space-x-2 sm:space-x-4">
                <!-- モバイル用ハンバーガーメニュー -->
                <button id="mobile-hamburger-menu" class="md:hidden mr-2 text-gray-700 p-2 rounded-full hover:bg-gray-100">
                    <span class="material-icons">menu</span>
                </button>

                <!-- AIディベートの場合は退出ボタンを表示 -->
                @if($debate->room->is_ai_debate)
                <form action="{{ route('ai.debate.exit', $debate) }}" method="POST"
                    onSubmit="return confirm('{{ __('messages.confirm_exit_ai_debate') }}');">
                    @csrf
                    <button type="submit"
                        class="btn-danger flex items-center px-2 py-1 sm:px-3 sm:py-1.5 text-xs rounded-lg transition-all hover:scale-105">
                        <span class="material-icons-outlined mr-1 text-sm">exit_to_app</span>
                        {{ __('messages.exit_debate') }}
                    </button>
                </form>
                @endif

                <!-- 全画面切替ボタン -->
                <button id="fullscreen-toggle" class="text-gray-700 p-2 rounded-full hover:bg-gray-100">
                    <span class="material-icons fullscreen-icon">fullscreen</span>
                </button>

                <!-- 現在のターン表示 -->
                <div class="flex flex-col items-center text-center">
                    <div class="px-3 py-1 rounded-full text-sm font-medium flex items-center {{
                        $isMyTurn ? 'bg-primary-light text-primary' : (
                        $isAITurn ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                        )
                    }}">
                        {{-- AIアイコン --}}
                        @if($isAITurn)
                            <span class="material-icons-outlined text-sm mr-1">smart_toy</span>
                        @endif
                        {{-- サイド表示 --}}
                        <span>
                            {{ $currentSpeaker === 'affirmative' ? __('messages.affirmative_side_label') : ($currentSpeaker === 'negative' ? __('messages.negative_side_label') : '') }}
                        </span>
                        {{-- ターン名 --}}
                        <span class="ml-1">{{ $currentTurnName }}</span>
                    </div>
                    {{-- ターン状況テキスト --}}
                    <span class="text-xs text-gray-500 mt-0.5">
                        @if($isMyTurn)
                            {{ __('messages.your_turn') }}
                        @elseif($isAITurn)
                            {{ __('messages.ai_turn') }}
                        @elseif($isPrepTime)
                            {{ __('messages.prep_time') }}
                        @else
                            {{ __('messages.opponent_turn') }}
                        @endif
                    </span>
                </div>

                <!-- タイマー -->
                <div class="flex flex-col items-center">
                    <div class="text-2xl font-bold tabular-nums
                        " id="countdown-timer">
                    </div>
                    <span class="text-xs text-gray-500">{{ __('messages.remaining_time') }}</span>
                </div>

            </div>
        </div>
    </div>

    {{-- JavaScript is now handled by pages/debate-show.js --}}
</header>
