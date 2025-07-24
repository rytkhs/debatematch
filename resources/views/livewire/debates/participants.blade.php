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
        <div class="my-4 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <!-- 現在のターン -->
            <div class="p-4 {{ $currentSpeaker === 'affirmative' ? 'bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-400' : ($currentSpeaker === 'negative' ? 'bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-400' : 'bg-gray-50') }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 {{ $currentSpeaker === 'affirmative' ? 'bg-green-500' : ($currentSpeaker === 'negative' ? 'bg-red-500' : 'bg-gray-400') }} rounded-full animate-pulse"></div>
                            <span class="text-xs font-medium text-gray-600 tracking-wide">{{ __('debates_ui.current_turn_info') }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-normal text-gray-800 text-sm">
                            @if($currentSpeaker)
                                {{ $currentSpeaker === 'affirmative' ? __('debates_ui.affirmative_side_label') : __('debates_ui.negative_side_label') }}{{$currentTurnName}}
                            @else
                                {{$currentTurnName}}
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 次のターン -->
            @if($nextTurnName !== __('rooms.finished'))
                <div class="p-4 bg-gray-50 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
                                <span class="text-xs font-medium text-gray-600 tracking-wide">{{ __('debates_ui.next_turn_info') }}</span>
                            </div>
                            {{-- @if($nextSpeaker)
                                <span class="px-2 py-1 {{ $nextSpeaker === 'affirmative' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' }} rounded-full text-xs font-medium">
                                    {{ $nextSpeaker === 'affirmative' ? __('debates_ui.affirmative_side_label') : __('debates_ui.negative_side_label') }}
                                </span>
                            @endif --}}
                        </div>
                        <div class="text-right">
                            <span class=" text-black-500 px-3 py-1 rounded-full text-xs font-normal ">
                                @if($nextSpeaker)
                                    {{ $nextSpeaker === 'affirmative' ? __('debates_ui.affirmative_side_label') : __('debates_ui.negative_side_label') }}{{$nextTurnName}}
                                @else
                                    {{$nextTurnName}}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            @else
                <div class="p-4 bg-gray-50 border-t border-gray-100">
                    <div class="flex items-center justify-center">
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('rooms.finished') }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <!-- 早期終了コンポーネント -->
        <livewire:debates.early-termination :debate="$debate" />
    </div>
</div>
