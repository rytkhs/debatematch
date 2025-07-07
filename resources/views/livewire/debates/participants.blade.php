<div class="h-full flex flex-col p-4 overflow-auto">
    <h2 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
        <span class="material-icons mr-2">people</span>{{ __('debates_ui.debaters') }}
    </h2>

    @php
        $aiUserId = (int)config('app.ai_user_id', 1);
        $isAffirmativeAI = $debate->affirmative_user_id === $aiUserId;
        $isNegativeAI = $debate->negative_user_id === $aiUserId;
    @endphp

    <!-- 肯定側 -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <h3 class="font-medium text-green-600 flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>{{ __('debates_ui.affirmative_side_label') }}
            </h3>
            @if($currentSpeaker === 'affirmative')
                <span class="px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded-full">{{ __('debates_ui.speaking') }}</span>
            @endif
        </div>

        <div class="flex items-center p-3 {{ $currentSpeaker === 'affirmative' ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200' }} rounded-lg">
            {{-- アバター --}}
            <div class="w-10 h-10 rounded-full {{ $isAffirmativeAI ? 'bg-blue-200 text-blue-700' : 'bg-green-200 text-green-700' }} flex items-center justify-center mr-3 flex-shrink-0">
                @if($isAffirmativeAI)
                    <span class="material-icons-outlined text-xl">smart_toy</span>
                @else
                    {{ $debate->affirmativeUser ? mb_substr($debate->affirmativeUser->name, 0, 2) : '??' }}
                @endif
            </div>
            <div>
                {{-- 名前 --}}
                <div class="font-medium flex items-center">
                    {{ $debate->affirmativeUser ? $debate->affirmativeUser->name : __('rooms.unknown_user') }}
                    @if($isAffirmativeAI)
                        <span class="ml-1.5 px-1.5 py-0.5 bg-blue-100 text-blue-800 text-[10px] rounded-full font-semibold">{{ __('ai_debate.ai_label') }}</span>
                    @endif
                </div>
                {{-- オンライン状態 --}}
                @if(!$isAffirmativeAI)
                    <div class="flex items-center mt-1 text-xs text-gray-500">
                        <span class="w-2 h-2 {{ $this->isUserOnline($debate->affirmative_user_id) ? 'bg-green-500' : 'bg-gray-400' }} rounded-full mr-1"></span>
                        {{ $this->isUserOnline($debate->affirmative_user_id) ? __('debates_ui.online') : __('debates_ui.offline') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 否定側 -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <h3 class="font-medium text-red-600 flex items-center">
                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>{{ __('debates_ui.negative_side_label') }}
            </h3>
            @if($currentSpeaker === 'negative')
                <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs rounded-full">{{ __('debates_ui.speaking') }}</span>
            @endif
        </div>

        <div class="flex items-center p-3 {{ $currentSpeaker === 'negative' ? 'bg-red-50 border border-red-200' : 'bg-gray-50 border border-gray-200' }} rounded-lg">
             {{-- アバター --}}
            <div class="w-10 h-10 rounded-full {{ $isNegativeAI ? 'bg-blue-200 text-blue-700' : 'bg-red-200 text-red-700' }} flex items-center justify-center mr-3 flex-shrink-0">
                @if($isNegativeAI)
                    <span class="material-icons-outlined text-xl">smart_toy</span>
                @else
                    {{ $debate->negativeUser ? mb_substr($debate->negativeUser->name, 0, 2) : '??' }}
                @endif
            </div>
            <div>
                 {{-- 名前 --}}
                <div class="font-medium flex items-center">
                    {{ $debate->negativeUser ? $debate->negativeUser->name : __('rooms.unknown_user') }}
                     @if($isNegativeAI)
                        <span class="ml-1.5 px-1.5 py-0.5 bg-blue-100 text-blue-800 text-[10px] rounded-full font-semibold">{{ __('ai_debate.ai_label') }}</span>
                    @endif
                </div>
                 {{-- オンライン状態 --}}
                @if(!$isNegativeAI)
                    <div class="flex items-center mt-1 text-xs text-gray-500">
                        <span class="w-2 h-2 {{ $this->isUserOnline($debate->negative_user_id) ? 'bg-green-500' : 'bg-gray-400' }} rounded-full mr-1"></span>
                        {{ $this->isUserOnline($debate->negative_user_id) ? __('debates_ui.online') : __('debates_ui.offline') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ターン終了ボタン -->
    <div class="mt-auto">
        @if($isMyTurn)
        <button wire:click="advanceTurnManually"
            wire:confirm="{{ __('debates_ui.confirm_end_turn', ['currentTurnName' => $currentTurnName, 'nextTurnName' => $nextTurnName]) }}"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-50 cursor-not-allowed"
            wire:target="advanceTurnManually"
            @if($isProcessing) disabled @endif
            class="w-full bg-primary hover:bg-primary-dark text-white font-bold py-3 px-4 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50 {{ $isProcessing ? 'opacity-50 cursor-not-allowed' : '' }}">
            <span class="flex items-center justify-center">
                <span class="material-icons mr-2">arrow_forward</span>
                <span>{{ $isProcessing ? __('common.processing') : __('debates_ui.end_turn') }}</span>
            </span>
        </button>
        @endif

        <!-- ターン情報 -->
        <div class="my-4 p-3 bg-gray-100 rounded-lg border border-gray-200">
            <p class="text-xs text-gray-600 mb-2">{{ __('debates_ui.current_turn_info') }}</p>
            <div class="flex items-center justify-between text-sm">
                <span class="font-medium">{{$currentTurnName}}</span>
                <span class="bg-primary-light text-primary px-2 py-0.5 rounded-full text-xs">
                    {{ __('debates_ui.remaining_time_label') }} <span wire:ignore id="time-left-small"></span>
                </span>
            </div>
        </div>
        <!-- 早期終了コンポーネント -->
        <livewire:debates.early-termination :debate="$debate" />
    </div>
</div>
