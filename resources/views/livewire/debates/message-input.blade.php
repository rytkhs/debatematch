<div class="relative p-3">
    <form wire:submit.prevent="sendMessage" class="relative">
        <!-- ツールバー -->
        <div class="flex items-center mb-2 px-2">
            <!-- サイズ調整ハンドル -->
            <div id="resize-handle" class="mr-2 cursor-ns-resize text-gray-400 hover:text-gray-600">
                <span class="material-icons">drag_handle</span>
            </div>

            <!-- 入力エリア操作ボタン -->
            <div class="flex items-center space-x-1 mr-2">
                <button type="button" id="expand-input" class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                    <span class="material-icons text-sm">unfold_more</span>
                </button>
                <button type="button" id="shrink-input" class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                    <span class="material-icons text-sm">unfold_less</span>
                </button>
                <button type="button" id="toggle-input-visibility" class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                    <span class="material-icons text-sm">visibility</span>
                </button>
            </div>

            <!-- 現在の入力者表示 -->
            @if($isPrepTime)
                <div class="text-sm text-gray-500 flex items-center">
                    <span class="material-icons text-sm mr-1">timer</span>
                    {{ __('messages.prep_time_in_progress') }}
                </div>
            @elseif($isMyTurn)
                <div class="text-sm text-primary font-medium flex items-center">
                    <span class="material-icons text-sm mr-1">edit</span>
                    {{ __('messages.ready_to_send') }}
                </div>
            {{-- AIターン中の表示 --}}
            @elseif(!$isMyTurn && !$isPrepTime && !$isQuestioningTurn && $debate->room->is_ai_debate)
                <div class="text-sm text-blue-500 flex items-center animate-pulse">
                    <span class="material-icons-outlined text-sm mr-1">smart_toy</span>
                    {{ __('messages.ai_thinking') }}
                </div>
            @else
                <div class="text-sm text-gray-500 flex items-center">
                    {{ $isQuestioningTurn ? __('messages.questioning_in_progress') : '' }}
                </div>
            @endif

            <!-- 音声認識中間結果表示エリア -->
            <div wire:ignore id="voice-interim-results" class="hidden md:flex flex-1 ml-2 mr-2 relative">
                <div class="min-h-[1.5rem] overflow-hidden text-sm font-medium text-primary-dark bg-primary-50 rounded-lg px-2 py-1 ml-4 text-ellipsis transition-opacity duration-200 opacity-0"></div>
            </div>

            <!-- 音声入力ボタン -->
            <button wire:ignore type="button" id="voice-input-toggle" class="hidden md:block ml-auto p-1.5 rounded-full transition-all duration-200 hover:bg-gray-200 text-gray-500 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50" title="{{ __('messages.voice_input') }}">
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
                placeholder="{{ ($isMyTurn || $isQuestioningTurn) && !$isPrepTime ? __('messages.enter_message_placeholder') : __('messages.cannot_send_message_now') }}"
                class="flex-1 border rounded-lg px-4 py-2 focus:ring-primary focus:border-primary resize-none overflow-y-auto bg-white disabled:bg-gray-100"
                maxlength="5000"
                rows="3"
            ></textarea>

            <button
                type="submit"
                {{ ($isMyTurn || $isQuestioningTurn) && !$isPrepTime ? '' : 'disabled' }}
                class="ml-2 w-10 h-10 rounded-full flex items-center justify-center self-end transition-colors duration-200
                       {{ ($isMyTurn || $isQuestioningTurn) && !$isPrepTime
                          ? 'bg-primary hover:bg-primary-dark text-white'
                          : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}"
            >
                <span class="material-icons">send</span>
            </button>
        </div>
    </form>

    @script
    <script>
    document.addEventListener('livewire:initialized', function() {
        const messageInput = document.getElementById('message-input');
        const resizeHandle = document.getElementById('resize-handle');
        const expandInput = document.getElementById('expand-input');
        const shrinkInput = document.getElementById('shrink-input');
        const toggleInputVisibility = document.getElementById('toggle-input-visibility');
        const inputArea = document.getElementById('input-area');
        const voiceInputToggle = document.getElementById('voice-input-toggle');
        const voiceInterimResults = document.getElementById('voice-interim-results');

        // 状態管理
        let state = {
            isResizing: false,
            startY: 0,
            startHeight: 0,
            isVisible: true,
            defaultHeight: 72,
            expandedHeight: window.innerHeight * 0.3,
            isAnimating: false,
            isVoiceRecognizing: false,
        };

        // 初期高さ設定
        if (messageInput) {
             state.defaultHeight = messageInput.offsetHeight;
        }

        // 保存された高さを復元
        if (messageInput) {
            const savedHeight = localStorage.getItem('debate_messageInputHeight');
            if (savedHeight) {
                messageInput.style.height = savedHeight;
            }

            // 保存された表示状態を復元
            const savedVisibility = localStorage.getItem('debate_messageInputVisibility');
            if (savedVisibility === 'hidden') {
                if(inputArea) inputArea.classList.add('hidden');
                if(toggleInputVisibility) toggleInputVisibility.querySelector('.material-icons').textContent = 'visibility_off';
                state.isVisible = false;
            }
        }

        // リサイズハンドル機能
        if (resizeHandle && messageInput) {
            // マウスイベント
            resizeHandle.addEventListener('mousedown', function(e) {
                state.isResizing = true;
                state.startY = e.clientY;
                state.startHeight = parseInt(getComputedStyle(messageInput).height, 10);
                ensureInputVisible();
                state.isAnimating = false;
                messageInput.style.transition = "none";

                document.addEventListener('mousemove', handleMouseMove);
                document.addEventListener('mouseup', handleMouseUp);
                e.preventDefault();
            });

            // タッチイベント（モバイル用）
            resizeHandle.addEventListener('touchstart', function(e) {
                if (e.touches.length !== 1) return;

                state.isResizing = true;
                state.startY = e.touches[0].clientY;
                state.startHeight = parseInt(getComputedStyle(messageInput).height, 10);
                ensureInputVisible();
                state.isAnimating = false;
                messageInput.style.transition = "none";

                document.addEventListener('touchmove', handleTouchMove);
                document.addEventListener('touchend', handleTouchEnd);
                e.preventDefault();
            });
        }

        // 入力エリア拡大ボタン
        if (expandInput && messageInput) {
            expandInput.addEventListener('click', function() {
                const maxHeightPercentage = window.innerWidth < 768 ? 0.7 : 0.73;
                state.expandedHeight = window.innerHeight * maxHeightPercentage;
                messageInput.style.transition = "height 0.2s ease";
                messageInput.style.height = `${state.expandedHeight}px`;
                saveInputHeight();
                ensureInputVisible();
                state.isAnimating = true;
            });
        }

        // 入力エリア縮小ボタン
        if (shrinkInput && messageInput) {
            shrinkInput.addEventListener('click', function() {
                messageInput.style.transition = "height 0.2s ease";
                messageInput.style.height = `${state.defaultHeight}px`;
                saveInputHeight();
                ensureInputVisible();
                state.isAnimating = true;
            });
        }

        // 入力エリア表示/非表示ボタン
        if (toggleInputVisibility && inputArea) {
            toggleInputVisibility.addEventListener('click', function() {
                if (state.isVisible) {
                    inputArea.classList.add('hidden');
                    toggleInputVisibility.querySelector('.material-icons').textContent = 'visibility_off';
                    state.isVisible = false;
                } else {
                    inputArea.classList.remove('hidden');
                    toggleInputVisibility.querySelector('.material-icons').textContent = 'visibility';
                    state.isVisible = true;
                }
                saveInputVisibility(state.isVisible);
            });
        }

        // マウス移動処理
        function handleMouseMove(e) {
            if (!state.isResizing) return;
            const deltaY = state.startY - e.clientY;
            const maxHeightPercentage = window.innerWidth < 768 ? 0.6 : 0.73;
            const newHeight = Math.max(60, Math.min(window.innerHeight * maxHeightPercentage, state.startHeight + deltaY));
            messageInput.style.height = `${newHeight}px`;
        }

        // タッチ移動処理
        function handleTouchMove(e) {
            if (!state.isResizing || e.touches.length !== 1) return;
            const deltaY = state.startY - e.touches[0].clientY;
            const maxHeightPercentage = window.innerWidth < 768 ? 0.6 : 0.73;
            const newHeight = Math.max(60, Math.min(window.innerHeight * maxHeightPercentage, state.startHeight + deltaY));
            messageInput.style.height = `${newHeight}px`;
        }

        // マウスアップ処理
        function handleMouseUp() {
            if (!state.isResizing) return;
            state.isResizing = false;
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
            messageInput.style.transition = "";
            state.isAnimating = false;
            saveInputHeight();
        }

        // タッチ終了処理
        function handleTouchEnd() {
            if (!state.isResizing) return;
            state.isResizing = false;
            document.removeEventListener('touchmove', handleTouchMove);
            document.removeEventListener('touchend', handleTouchEnd);
            messageInput.style.transition = "";
            state.isAnimating = false;
            saveInputHeight();
        }

        // 入力エリアの高さを保存
        function saveInputHeight() {
            if (messageInput) {
                localStorage.setItem('debate_messageInputHeight', messageInput.style.height);
            }
        }

        // 入力エリアの表示状態を保存
        function saveInputVisibility(isVisible) {
            localStorage.setItem('debate_messageInputVisibility', isVisible ? 'visible' : 'hidden');
        }

        // 入力エリアが非表示の場合に表示する関数
        function ensureInputVisible() {
             if (!state.isVisible && inputArea && toggleInputVisibility) {
                inputArea.classList.remove('hidden');
                toggleInputVisibility.querySelector('.material-icons').textContent = 'visibility';
                state.isVisible = true;
                saveInputVisibility(state.isVisible);
            }
        }

        // 音声認識機能
        // SpeechRecognition または webkitSpeechRecognition が利用可能かチェック
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (voiceInputToggle && messageInput && SpeechRecognition) {
            let recognition = new SpeechRecognition();
            let currentMessageValue = '';

            // 言語設定
            const roomLanguage = "{{ $debate->room->language ?? 'english' }}";
            recognition.lang = roomLanguage === 'japanese' ? 'ja-JP' : 'en-US';

            recognition.continuous = true;
            recognition.interimResults = true;

            // 音声認識開始時の処理
            recognition.onstart = function() {
                state.isVoiceRecognizing = true;
                voiceInputToggle.querySelector('.material-icons').textContent = 'mic';
                voiceInputToggle.classList.remove('hover:bg-gray-200', 'text-gray-500');
                voiceInputToggle.classList.add('bg-primary', 'text-white', 'animate-pulse');

                // 現在のテキストエリアの値を保存
                currentMessageValue = messageInput.value;
            };

            // 音声認識結果の処理
            recognition.onresult = function(event) {
                let finalTranscript = '';
                let interimTranscript = '';

                // 結果を処理
                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    if (event.results[i].isFinal) {
                        finalTranscript += event.results[i][0].transcript;
                    } else {
                        interimTranscript += event.results[i][0].transcript;
                    }
                }

                // 中間結果を表示エリアに表示
                if (voiceInterimResults) {
                    const resultElement = voiceInterimResults.querySelector('div');
                    if (resultElement) {
                        resultElement.textContent = interimTranscript;

                        // 表示/非表示の切り替え
                        if (interimTranscript) {
                            resultElement.style.opacity = '1';
                        } else {
                            resultElement.style.opacity = '0';
                        }
                    }
                }

                // 最終結果があればテキストエリアとLivewireモデルを更新
                if (finalTranscript) {
                    let updatedText = currentMessageValue + finalTranscript;
                    messageInput.value = updatedText;
                    currentMessageValue = updatedText;
                    @this.set('newMessage', updatedText);

                    // 最終結果が確定したら中間結果表示をクリア
                    if (voiceInterimResults) {
                        const resultElement = voiceInterimResults.querySelector('div');
                        if (resultElement) {
                            resultElement.textContent = '';
                            resultElement.style.opacity = '0';
                        }
                    }
                }
            };

            // 音声認識終了時の処理
            recognition.onend = function() {
                if (state.isVoiceRecognizing) {
                    // 自動的に終了した場合は再開
                    recognition.start();
                } else {
                    // 手動で停止した場合
                    voiceInputToggle.querySelector('.material-icons').textContent = 'mic';
                    voiceInputToggle.classList.remove('bg-primary', 'text-white', 'animate-pulse');
                    voiceInputToggle.classList.add('hover:bg-gray-200', 'text-gray-500');
                }
                // 認識終了時（エラー時も）に中間結果表示をクリア
                if (voiceInterimResults) {
                    const resultElement = voiceInterimResults.querySelector('div');
                    if (resultElement) {
                        resultElement.textContent = '';
                        resultElement.style.opacity = '0';
                    }
                }
            };

            // エラー処理
            recognition.onerror = function(event) {
                console.error('音声認識エラー:', event.error);
                state.isVoiceRecognizing = false;
                voiceInputToggle.querySelector('.material-icons').textContent = 'mic';
                voiceInputToggle.classList.remove('bg-primary', 'text-white', 'animate-pulse');
                voiceInputToggle.classList.add('hover:bg-gray-200', 'text-gray-500');
                // エラー発生時に中間結果表示をクリア
                if (voiceInterimResults) {
                    const resultElement = voiceInterimResults.querySelector('div');
                    if (resultElement) {
                        resultElement.textContent = '';
                        resultElement.style.opacity = '0';
                    }
                }
            };

            // 音声入力ボタンのクリックイベント
            voiceInputToggle.addEventListener('click', function() {
                if (state.isVoiceRecognizing) {
                    // 音声認識停止
                    recognition.stop();
                    state.isVoiceRecognizing = false;
                } else {
                    // 音声認識開始
                    try {
                        recognition.start();
                        ensureInputVisible(); // 入力エリアが非表示の場合は表示する
                    } catch (e) {
                        console.error('{{ __('messages.voice_input_failed') }}', e);
                        // エラー発生時にも状態をリセット
                        state.isVoiceRecognizing = false;
                        voiceInputToggle.querySelector('.material-icons').textContent = 'mic';
                        voiceInputToggle.classList.remove('bg-primary', 'text-white', 'animate-pulse');
                        voiceInputToggle.classList.add('hover:bg-gray-200', 'text-gray-500');
                    }
                }
            });

            // テキストエリアへの通常入力時の処理 カーソル位置を考慮した更新
            messageInput.addEventListener('input', function(e) {
                // 音声認識中かどうかに関わらず、テキストエリアの値が変更されたらLivewireモデルを更新
                // これにより、ユーザーが手動で編集した場合もnewMessageプロパティが最新の状態に保たれる
                @this.set('newMessage', messageInput.value);

                // 音声認識中の場合、現在の保存値も更新
                if (state.isVoiceRecognizing) {
                    currentMessageValue = messageInput.value;
                }
            });

        } else if (voiceInputToggle) {
            // 音声認識非対応環境では音声入力ボタンを無効化
            voiceInputToggle.disabled = true;
            voiceInputToggle.title = "{{ __('messages.browser_does_not_support_voice_input') }}";
            voiceInputToggle.classList.add('opacity-50', 'cursor-not-allowed');
        }
    });
    </script>
    @endscript
</div>
