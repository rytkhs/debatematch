<header
    class="flex-none bg-white border-b border-gray-200 p-4 shadow-sm z-30"
    x-data
    x-init="initializeDebatePage({ debateId: {{ $debate->id }}, isAiDebate: {{ $debate->room->is_ai_debate ? 'true' : 'false' }} })"
>
    <div class="flex justify-between items-center">
        <!-- 左側: ディベート情報 -->
        <div class="flex items-center space-x-4">
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
                onSubmit="return confirm('{{ __('ai_debate.confirm_exit_ai_debate') }}');">
                @csrf
                <button type="submit"
                    class="btn-danger flex items-center px-2 py-1 sm:px-3 sm:py-1.5 text-xs rounded-lg transition-all hover:scale-105">
                    <span class="material-icons-outlined mr-1 text-sm">exit_to_app</span>
                    {{ __('ai_debate.exit_debate') }}
                </button>
            </form>
            @endif

            <!-- AI準備時間スキップボタン -->
            @if($canSkipAIPrepTime)
            <button
                wire:click="skipAIPrepTime"
                wire:loading.attr="disabled"
                wire:confirm="{{ __('ai_debate.confirm_skip_prep_time') }}"
                class="btn-secondary flex items-center px-2 py-1 sm:px-3 sm:py-1.5 text-xs rounded-lg transition-all hover:scale-105"
                {{ $remainingTime < 5 ? 'disabled' : '' }}
            >
                <span class="material-icons-outlined mr-1 text-sm">skip_next</span>
                {{ __('ai_debate.skip_prep_time') }}
            </button>
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
                        {{ $currentSpeaker === 'affirmative' ? __('debates_ui.affirmative_side_label') : ($currentSpeaker === 'negative' ? __('debates_ui.negative_side_label') : '') }}
                    </span>
                    {{-- ターン名 --}}
                    <span class="ml-1">{{ $currentTurnName }}</span>
                </div>
                {{-- ターン状況テキスト --}}
                <span class="text-xs text-gray-500 mt-0.5">
                    @if($isMyTurn)
                        {{ __('debates_ui.your_turn') }}
                    @elseif($isAITurn)
                        {{ __('ai_debate.ai_turn') }}
                    @elseif($isPrepTime)
                        {{ __('debates_format.prep_time') }}
                    @else
                        {{ __('debates_ui.opponent_turn') }}
                    @endif
                </span>
            </div>

            <!-- タイマー -->
            <div class="flex flex-col items-center">
                <div class="text-2xl font-bold tabular-nums
                    " id="countdown-timer">
                </div>
                <span class="text-xs text-gray-500">{{ __('debates_ui.remaining_time') }}</span>
            </div>

        </div>
    </div>
</header>
