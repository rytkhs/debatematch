<div class="relative p-3">
    <form wire:submit.prevent="sendMessage" class="relative">
        <!-- ツールバー -->
        <div class="flex items-center mb-2 px-2">
            <!-- サイズ調整ハンドル -->
            <div id="resize-handle" class="mr-2 cursor-ns-resize text-gray-400 hover:text-gray-600" title="{{ __('debates_ui.resize_input_area') }}">
                <span class="material-icons">drag_handle</span>
            </div>

            <!-- 入力エリア操作ボタン -->
            <div class="flex items-center space-x-1 mr-2">
                <button type="button" id="expand-input" class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100" title="{{ __('debates_ui.expand_input_area') }} (Ctrl+Alt+↑)">
                    <span class="material-icons text-sm">unfold_more</span>
                </button>
                <button type="button" id="shrink-input" class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100" title="{{ __('debates_ui.shrink_input_area') }} (Ctrl+Alt+↓)">
                    <span class="material-icons text-sm">unfold_less</span>
                </button>
                <button type="button" id="toggle-input-visibility" class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100" title="{{ __('debates_ui.toggle_input_visibility') }} (Ctrl+Alt+H)">
                    <span class="material-icons text-sm">visibility</span>
                </button>
            </div>

            <!-- 現在の入力者表示 -->
            @if($isPrepTime)
                <div class="text-sm text-gray-500 flex items-center">
                    <span class="material-icons text-sm mr-1">timer</span>
                    {{ __('debates_ui.prep_time_in_progress') }}
                </div>
            @elseif($isMyTurn)
                <div class="text-sm text-primary font-medium flex items-center">
                    <span class="material-icons text-sm mr-1">edit</span>
                    {{ __('debates_ui.ready_to_send') }}
                </div>
            {{-- AIターン中の表示 --}}
            @elseif(!$isMyTurn && !$isPrepTime && !$isQuestioningTurn && $debate->room->is_ai_debate)
                <div class="text-sm text-blue-500 flex items-center animate-pulse">
                    <span class="material-icons-outlined text-sm mr-1">smart_toy</span>
                    {{ __('ai_debate.ai_thinking') }}
                </div>
            @else
                <div class="text-sm text-gray-500 flex items-center">
                    {{ $isQuestioningTurn ? __('debates_ui.questioning_in_progress') : '' }}
                </div>
            @endif

            <!-- 音声認識中間結果表示エリア -->
            <div wire:ignore id="voice-interim-results" class="hidden md:flex flex-1 ml-2 mr-2 relative">
                <div class="min-h-[1.5rem] overflow-hidden text-sm font-medium text-primary-dark bg-primary-50 rounded-lg px-2 py-1 ml-4 text-ellipsis transition-opacity duration-200 opacity-0"></div>
            </div>

            <!-- 音声入力ボタン -->
            <button wire:ignore type="button" id="voice-input-toggle" class="hidden md:block ml-auto p-1.5 rounded-full transition-all duration-200 hover:bg-gray-200 text-gray-500 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50" title="{{ __('misc.voice_input') }} (Ctrl+Alt+V)">
                <span class="material-icons text-base">mic</span>
            </button>

            <!-- 文字数カウンター -->
            <div class="ml-2 text-xs text-gray-500">
                <span x-text="$wire.newMessage.length"></span>/5000
            </div>
        </div>

        <!-- 入力エリア -->
        <div class="flex items-start" id="input-area">
            <textarea
                id="message-input"
                wire:model.live="newMessage"
                placeholder="{{ ($isMyTurn || $isQuestioningTurn) && !$isPrepTime ? __('debates_ui.enter_message_placeholder') : __('debates_ui.cannot_send_message_now') }}"
                class="flex-1 border rounded-lg px-4 py-2 focus:ring-primary focus:border-primary resize-none overflow-y-auto bg-white disabled:bg-gray-100"
                maxlength="5000"
                rows="3"
            ></textarea>

            <button
                type="submit"
                {{ ($isMyTurn || $isQuestioningTurn) && !$isPrepTime ? '' : 'disabled' }}
                title="{{ ($isMyTurn || $isQuestioningTurn) && !$isPrepTime ? __('debates_ui.send_message') : __('debates_ui.cannot_send_message_now') }}"
                class="ml-2 w-10 h-10 rounded-full flex items-center justify-center self-end transition-colors duration-200 relative group
                       {{ ($isMyTurn || $isQuestioningTurn) && !$isPrepTime
                          ? 'bg-primary hover:bg-primary-dark text-white'
                          : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}"
            >
                <span class="material-icons">send</span>
                <!-- ホバー時のキーボードショートカット表示 -->
                @if(($isMyTurn || $isQuestioningTurn) && !$isPrepTime)
                <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap pointer-events-none">
                    Ctrl+Enter
                </div>
                @endif
            </button>
        </div>
    </form>

        {{--
        インラインスクリプトはresources/js/features/debate/input-area.jsに移行されました
        初期化はpages/debate-show.jsから行われます
    --}}

</div>
