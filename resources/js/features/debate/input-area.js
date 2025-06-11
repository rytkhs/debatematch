/**
 * メッセージ入力エリア管理
 * 入力エリアのリサイズ、音声認識、キーボードショートカットなどを管理
 */
class InputAreaManager {
    constructor() {
        this.elements = {};
        this.state = {
            isResizing: false,
            startY: 0,
            startHeight: 0,
            isVisible: true,
            defaultHeight: 72,
            expandedHeight: window.innerHeight * 0.3,
            isAnimating: false,
            isVoiceRecognizing: false,
        };
        this.recognition = null;
        this.currentMessageValue = '';
    }

    /**
     * 入力エリア管理を初期化
     */
    initialize() {
        this.initializeElements();
        this.initializeState();
        this.setupEventListeners();
        this.initializeVoiceRecognition();
        this.setupKeyboardShortcuts();
    }

    /**
     * DOM要素を初期化
     */
    initializeElements() {
        this.elements = {
            messageInput: document.getElementById('message-input'),
            resizeHandle: document.getElementById('resize-handle'),
            expandInput: document.getElementById('expand-input'),
            shrinkInput: document.getElementById('shrink-input'),
            toggleInputVisibility: document.getElementById('toggle-input-visibility'),
            inputArea: document.getElementById('input-area'),
            voiceInputToggle: document.getElementById('voice-input-toggle'),
            voiceInterimResults: document.getElementById('voice-interim-results')
        };
    }

    /**
     * 初期状態を設定
     */
    initializeState() {
        const { messageInput, inputArea, toggleInputVisibility } = this.elements;

        // 初期高さ設定
        if (messageInput) {
            this.state.defaultHeight = messageInput.offsetHeight;
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
                if (inputArea) inputArea.classList.add('hidden');
                if (toggleInputVisibility) toggleInputVisibility.querySelector('.material-icons').textContent = 'visibility_off';
                this.state.isVisible = false;
            }
        }
    }

    /**
     * イベントリスナーを設定
     */
    setupEventListeners() {
        this.setupResizeHandlers();
        this.setupButtonHandlers();
        this.setupInputHandlers();
    }

    /**
     * リサイズハンドラーを設定
     */
    setupResizeHandlers() {
        const { resizeHandle, messageInput } = this.elements;

        if (resizeHandle && messageInput) {
            // マウスイベント
            resizeHandle.addEventListener('mousedown', (e) => {
                this.startResize(e.clientY);
                document.addEventListener('mousemove', this.handleMouseMove.bind(this));
                document.addEventListener('mouseup', this.handleMouseUp.bind(this));
                e.preventDefault();
            });

            // タッチイベント（モバイル用）
            resizeHandle.addEventListener('touchstart', (e) => {
                if (e.touches.length !== 1) return;
                this.startResize(e.touches[0].clientY);
                document.addEventListener('touchmove', this.handleTouchMove.bind(this));
                document.addEventListener('touchend', this.handleTouchEnd.bind(this));
                e.preventDefault();
            });
        }
    }

    /**
     * ボタンハンドラーを設定
     */
    setupButtonHandlers() {
        const { expandInput, shrinkInput, toggleInputVisibility, inputArea, messageInput } = this.elements;

        // 入力エリア拡大ボタン
        if (expandInput && messageInput) {
            expandInput.addEventListener('click', () => {
                const maxHeightPercentage = window.innerWidth < 768 ? 0.7 : 0.73;
                this.state.expandedHeight = window.innerHeight * maxHeightPercentage;
                messageInput.style.transition = "height 0.2s ease";
                messageInput.style.height = `${this.state.expandedHeight}px`;
                this.saveInputHeight();
                this.ensureInputVisible();
                this.state.isAnimating = true;
            });
        }

        // 入力エリア縮小ボタン
        if (shrinkInput && messageInput) {
            shrinkInput.addEventListener('click', () => {
                messageInput.style.transition = "height 0.2s ease";
                messageInput.style.height = `${this.state.defaultHeight}px`;
                this.saveInputHeight();
                this.ensureInputVisible();
                this.state.isAnimating = true;
            });
        }

        // 入力エリア表示/非表示ボタン
        if (toggleInputVisibility && inputArea) {
            toggleInputVisibility.addEventListener('click', () => {
                if (this.state.isVisible) {
                    inputArea.classList.add('hidden');
                    toggleInputVisibility.querySelector('.material-icons').textContent = 'visibility_off';
                    this.state.isVisible = false;
                } else {
                    inputArea.classList.remove('hidden');
                    toggleInputVisibility.querySelector('.material-icons').textContent = 'visibility';
                    this.state.isVisible = true;
                }
                this.saveInputVisibility(this.state.isVisible);
            });
        }
    }

    /**
     * 入力ハンドラーを設定
     */
    setupInputHandlers() {
        const { messageInput } = this.elements;

        if (messageInput) {
            // テキストエリアへの通常入力時の処理
            messageInput.addEventListener('input', (e) => {
                // Livewireモデルを更新
                if (window.Livewire && typeof window.Livewire.find !== 'undefined') {
                    // Livewireコンポーネントを見つけて更新
                    const component = window.Livewire.find(messageInput.closest('[wire\\:id]')?.getAttribute('wire:id'));
                    if (component) {
                        component.set('newMessage', messageInput.value);
                    }
                }

                // 音声認識中の場合、現在の保存値も更新
                if (this.state.isVoiceRecognizing) {
                    this.currentMessageValue = messageInput.value;
                }
            });
        }
    }

    /**
     * リサイズを開始
     */
    startResize(clientY) {
        const { messageInput } = this.elements;
        this.state.isResizing = true;
        this.state.startY = clientY;
        this.state.startHeight = parseInt(getComputedStyle(messageInput).height, 10);
        this.ensureInputVisible();
        this.state.isAnimating = false;
        messageInput.style.transition = "none";
    }

    /**
     * マウス移動処理
     */
    handleMouseMove(e) {
        if (!this.state.isResizing) return;
        this.updateHeight(e.clientY);
    }

    /**
     * タッチ移動処理
     */
    handleTouchMove(e) {
        if (!this.state.isResizing || e.touches.length !== 1) return;
        this.updateHeight(e.touches[0].clientY);
    }

    /**
     * 高さを更新
     */
    updateHeight(clientY) {
        const { messageInput } = this.elements;
        const deltaY = this.state.startY - clientY;
        const maxHeightPercentage = window.innerWidth < 768 ? 0.6 : 0.73;
        const newHeight = Math.max(60, Math.min(window.innerHeight * maxHeightPercentage, this.state.startHeight + deltaY));
        messageInput.style.height = `${newHeight}px`;
    }

    /**
     * マウスアップ処理
     */
    handleMouseUp() {
        this.endResize();
        document.removeEventListener('mousemove', this.handleMouseMove.bind(this));
        document.removeEventListener('mouseup', this.handleMouseUp.bind(this));
    }

    /**
     * タッチ終了処理
     */
    handleTouchEnd() {
        this.endResize();
        document.removeEventListener('touchmove', this.handleTouchMove.bind(this));
        document.removeEventListener('touchend', this.handleTouchEnd.bind(this));
    }

    /**
     * リサイズを終了
     */
    endResize() {
        const { messageInput } = this.elements;
        if (!this.state.isResizing) return;
        this.state.isResizing = false;
        messageInput.style.transition = "";
        this.state.isAnimating = false;
        this.saveInputHeight();
    }

    /**
     * 音声認識を初期化
     */
    initializeVoiceRecognition() {
        const { voiceInputToggle, messageInput, voiceInterimResults } = this.elements;

        // SpeechRecognition または webkitSpeechRecognition が利用可能かチェック
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (voiceInputToggle && messageInput && SpeechRecognition) {
            this.recognition = new SpeechRecognition();

            // 言語設定
            const roomLanguage = document.documentElement.getAttribute('data-room-language') || 'english';
            this.recognition.lang = roomLanguage === 'japanese' ? 'ja-JP' : 'en-US';
            this.recognition.continuous = true;
            this.recognition.interimResults = true;

            this.setupVoiceRecognitionEvents();
            this.setupVoiceInputButton();
        }
    }

    /**
     * 音声認識イベントを設定
     */
    setupVoiceRecognitionEvents() {
        const { voiceInputToggle, messageInput, voiceInterimResults } = this.elements;

        // 音声認識開始時の処理
        this.recognition.onstart = () => {
            this.state.isVoiceRecognizing = true;
            voiceInputToggle.querySelector('.material-icons').textContent = 'mic';
            voiceInputToggle.classList.remove('hover:bg-gray-200', 'text-gray-500');
            voiceInputToggle.classList.add('bg-primary', 'text-white', 'animate-pulse');
            this.currentMessageValue = messageInput.value;
        };

        // 音声認識結果の処理
        this.recognition.onresult = (event) => {
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
                    resultElement.style.opacity = interimTranscript ? '1' : '0';
                }
            }

            // 最終結果があればテキストエリアとLivewireモデルを更新
            if (finalTranscript) {
                let updatedText = this.currentMessageValue + finalTranscript;
                messageInput.value = updatedText;
                this.currentMessageValue = updatedText;

                // Livewireモデルを更新
                if (window.Livewire) {
                    const component = window.Livewire.find(messageInput.closest('[wire\\:id]')?.getAttribute('wire:id'));
                    if (component) {
                        component.set('newMessage', updatedText);
                    }
                }

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
        this.recognition.onend = () => {
            if (this.state.isVoiceRecognizing) {
                // 自動的に終了した場合は再開
                this.recognition.start();
            } else {
                // 手動で停止した場合
                this.resetVoiceRecognitionUI();
            }
            // 認識終了時に中間結果表示をクリア
            this.clearInterimResults();
        };

        // エラー処理
        this.recognition.onerror = (event) => {
            console.error('音声認識エラー:', event.error);
            this.state.isVoiceRecognizing = false;
            this.resetVoiceRecognitionUI();
            this.clearInterimResults();
        };
    }

    /**
     * 音声入力ボタンを設定
     */
    setupVoiceInputButton() {
        const { voiceInputToggle } = this.elements;

        voiceInputToggle.addEventListener('click', () => {
            if (this.state.isVoiceRecognizing) {
                // 音声認識停止
                this.recognition.stop();
                this.state.isVoiceRecognizing = false;
            } else {
                // 音声認識開始
                try {
                    this.recognition.start();
                    this.ensureInputVisible(); // 入力エリアが非表示の場合は表示する
                } catch (e) {
                    console.error('音声入力に失敗しました', e);
                    this.state.isVoiceRecognizing = false;
                    this.resetVoiceRecognitionUI();
                }
            }
        });
    }

    /**
     * キーボードショートカットを設定
     */
    setupKeyboardShortcuts() {
        const { messageInput } = this.elements;

        if (messageInput) {
            messageInput.addEventListener('keydown', (e) => {
                // Ctrl+Enterキーで送信機能
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();

                    // 音声認識中は送信しない
                    if (this.state.isVoiceRecognizing) {
                        return;
                    }

                    // 送信可能な状態かチェック
                    const submitButton = document.querySelector('button[type="submit"]');
                    if (submitButton && !submitButton.disabled && messageInput.value.trim()) {
                        try {
                            // LivewireのsendMessageメソッドを呼び出し
                            const component = window.Livewire.find(messageInput.closest('[wire\\:id]')?.getAttribute('wire:id'));
                            if (component) {
                                component.call('sendMessage');
                            }
                        } catch (error) {
                            console.error('メッセージ送信エラー:', error);
                        }
                    }
                }
            });
        }

        // グローバルキーボードショートカット
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey && e.altKey) || (e.metaKey && e.altKey)) {
                const { expandInput, shrinkInput, toggleInputVisibility, voiceInputToggle } = this.elements;

                switch (e.key) {
                    case 'ArrowUp': // 拡大
                        e.preventDefault();
                        if (expandInput) expandInput.click();
                        break;
                    case 'ArrowDown': // 縮小
                        e.preventDefault();
                        if (shrinkInput) shrinkInput.click();
                        break;
                    case 'h':
                    case 'H': // 表示/非表示切替
                        e.preventDefault();
                        if (toggleInputVisibility) toggleInputVisibility.click();
                        break;
                    case 'v':
                    case 'V': // 音声入力切替
                        e.preventDefault();
                        if (voiceInputToggle) voiceInputToggle.click();
                        break;
                }
            }
        });
    }

    /**
     * 音声認識UIをリセット
     */
    resetVoiceRecognitionUI() {
        const { voiceInputToggle } = this.elements;
        voiceInputToggle.querySelector('.material-icons').textContent = 'mic';
        voiceInputToggle.classList.remove('bg-primary', 'text-white', 'animate-pulse');
        voiceInputToggle.classList.add('hover:bg-gray-200', 'text-gray-500');
    }

    /**
     * 中間結果を クリア
     */
    clearInterimResults() {
        const { voiceInterimResults } = this.elements;
        if (voiceInterimResults) {
            const resultElement = voiceInterimResults.querySelector('div');
            if (resultElement) {
                resultElement.textContent = '';
                resultElement.style.opacity = '0';
            }
        }
    }

    /**
     * 入力エリアの高さを保存
     */
    saveInputHeight() {
        const { messageInput } = this.elements;
        if (messageInput) {
            localStorage.setItem('debate_messageInputHeight', messageInput.style.height);
        }
    }

    /**
     * 入力エリアの表示状態を保存
     */
    saveInputVisibility(isVisible) {
        localStorage.setItem('debate_messageInputVisibility', isVisible ? 'visible' : 'hidden');
    }

    /**
     * 入力エリアが非表示の場合に表示する関数
     */
    ensureInputVisible() {
        const { inputArea, toggleInputVisibility } = this.elements;
        if (!this.state.isVisible && inputArea && toggleInputVisibility) {
            inputArea.classList.remove('hidden');
            toggleInputVisibility.querySelector('.material-icons').textContent = 'visibility';
            this.state.isVisible = true;
            this.saveInputVisibility(this.state.isVisible);
        }
    }
}

export default InputAreaManager;
