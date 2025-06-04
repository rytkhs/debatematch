<div>
    @if($isFreeFormat && $debate->room->status === 'debating')
        <div class="bg-gray-50 border border-gray-200 rounded-md p-3 mb-3">

            @if($earlyTerminationStatus['status'] === 'none')
                <!-- 早期終了提案ボタン -->
                @if($canRequest)
                <p class="text-xs text-gray-500 mb-2">
                        <span class="material-icons text-gray-400 text-sm mr-1">schedule</span>
                        @if($isAiDebate)
                            ディベートを早期終了できます
                        @else
                            ディベートの早期終了を提案できます
                        @endif
                    </p>
                    <button
                        onclick="confirmEarlyTermination()"
                        class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-1.5 px-3 rounded border transition-colors duration-200 flex items-center"
                        wire:loading.attr="disabled"
                        wire:target="requestEarlyTermination"
                    >
                        <span wire:loading.remove wire:target="requestEarlyTermination" class="flex items-center">
                            <span class="material-icons text-sm mr-1">flag</span>
                            @if($isAiDebate)
                                早期終了
                            @else
                                早期終了を提案
                            @endif
                        </span>
                        <span wire:loading wire:target="requestEarlyTermination" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-1 h-3 w-3 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('messages.processing') }}
                        </span>
                    </button>
                @else
                    <p class="text-xs text-gray-500 text-center py-2">
                        {{ __('messages.early_termination_request') }}は参加者のみ可能です
                    </p>
                @endif

            @elseif($earlyTerminationStatus['status'] === 'requested')
                @if($isRequester)
                    <!-- 提案者の場合：待機状態 -->
                    <div class="text-center">
                        <div class="flex items-center justify-center mb-2">
                            <svg class="animate-spin h-4 w-4 text-orange-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-xs font-medium text-orange-600">
                                {{ __('messages.early_termination_waiting_response') }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500">
                            相手の応答をお待ちください
                        </p>
                    </div>
                @elseif($canRespond)
                    <!-- 応答者の場合：同意/拒否ボタン -->
                    <div class="space-y-2">
                        <p class="text-xs text-gray-700 text-center font-medium">
                            {{ __('messages.early_termination_proposal', ['name' => $this->getOpponentName()]) }}
                        </p>
                        <div class="flex space-x-2">
                            <button
                                wire:click="respondToEarlyTermination(true)"
                                class="flex-1 bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-3 rounded text-xs transition-colors duration-200 flex items-center justify-center"
                                wire:loading.attr="disabled"
                                wire:target="respondToEarlyTermination"
                            >
                                <span wire:loading.remove wire:target="respondToEarlyTermination">
                                    {{ __('messages.early_termination_agree') }}
                                </span>
                                <span wire:loading wire:target="respondToEarlyTermination" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-1 h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                            <button
                                wire:click="respondToEarlyTermination(false)"
                                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-3 rounded text-xs transition-colors duration-200 flex items-center justify-center"
                                wire:loading.attr="disabled"
                                wire:target="respondToEarlyTermination"
                            >
                                <span wire:loading.remove wire:target="respondToEarlyTermination">
                                    {{ __('messages.early_termination_decline') }}
                                </span>
                                <span wire:loading wire:target="respondToEarlyTermination" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-1 h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    @endif

    <script>
        function confirmEarlyTermination() {
            const isAiDebate = @json($isAiDebate);
            const message = isAiDebate
                ? 'ディベートを早期終了しますか？'
                : 'ディベートの早期終了を提案しますか？相手の同意が必要です。';

            if (confirm(message)) {
                @this.requestEarlyTermination();
            }
        }
    </script>
</div>
