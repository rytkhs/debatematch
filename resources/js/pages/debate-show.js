import CountdownManager from '../features/debate/countdown-manager.js';
import CountdownTimer from '../components/countdown-timer.js';
import DebateEventHandler from '../features/debate/event-handler.js';
import DebatePresenceManager from '../features/debate/presence-manager.js';
import ChatScrollManager from '../features/debate/chat-scroll.js';
import DebateUIManager from '../features/debate/ui-manager.js';
import AudioHandler from '../features/debate/audio-handler.js';
import InputAreaManager from '../features/debate/input-area.js';

/**
 * ディベートページの統合管理クラス
 * すべてのディベート関連機能を初期化・統合管理
 */
class DebateShowManager {
    constructor() {
        this.managers = {};
        this.isInitialized = false;
    }

    /**
     * ディベートページを初期化
     */
    initialize() {
        if (this.isInitialized) return;

        // デバッグデータの確認
        if (typeof window.debateData === 'undefined') {
            console.error('window.debateData is not available');
            return;
        }

        // 各機能の初期化
        this.initializeCountdown();
        this.initializeEventHandler();
        this.initializePresenceManager();
        this.initializeChatScroll();
        this.initializeUIManager();
        this.initializeAudioHandler();
        this.initializeInputArea();

        // グローバル参照設定（後方互換性のため）
        this.setupGlobalReferences();

        // 早期終了機能の初期化
        this.initializeEarlyTermination();

        // Livewireコンポーネントの初期化（遅延実行）
        this.initializeLivewireComponentsDelayed();

        this.isInitialized = true;
    }

    /**
     * カウントダウン機能を初期化
     */
    initializeCountdown() {
        this.managers.countdownManager = new CountdownManager();
        this.managers.countdownTimer = new CountdownTimer(this.managers.countdownManager);

        // Livewireイベントの初期化
        this.managers.countdownManager.initLivewireEvents();
    }

    /**
     * イベントハンドラーを初期化
     */
    initializeEventHandler() {
        this.managers.eventHandler = new DebateEventHandler(window.debateData.debateId);
    }

    /**
     * プレゼンス管理を初期化
     */
    initializePresenceManager() {
        this.managers.presenceManager = new DebatePresenceManager(window.debateData);
        this.managers.presenceManager.initialize();
    }

    /**
     * チャットスクロール管理を初期化
     */
    initializeChatScroll() {
        this.managers.chatScrollManager = new ChatScrollManager();
        this.managers.chatScrollManager.initialize();
    }

    /**
     * UI管理を初期化
     */
    initializeUIManager() {
        this.managers.uiManager = new DebateUIManager();
        this.managers.uiManager.initialize();
    }

    /**
     * オーディオハンドラーを初期化
     */
    initializeAudioHandler() {
        this.managers.audioHandler = new AudioHandler(window.debateData);
        this.managers.audioHandler.initialize();
    }

    /**
     * 入力エリア管理を初期化
     */
    initializeInputArea() {
        // Livewireが初期化された後に入力エリアを初期化
        if (window.Livewire) {
            this.setupInputAreaAfterLivewire();
        } else {
            document.addEventListener('livewire:initialized', () => {
                this.setupInputAreaAfterLivewire();
            });
        }
    }

    /**
     * Livewire初期化後に入力エリアを設定
     */
    setupInputAreaAfterLivewire() {
        this.managers.inputAreaManager = new InputAreaManager();
        this.managers.inputAreaManager.initialize();
    }

    /**
     * グローバル参照を設定（後方互換性のため）
     */
    setupGlobalReferences() {
        // 既存のコードとの互換性のため、グローバル参照を設定
        window.debateCountdown = this.managers.countdownManager;
        window.debateShowManager = this;

        // カウントダウンをグローバルエクスポート
        if (this.managers.countdownManager) {
            // Livewire初期化後のイベント設定も維持
            document.addEventListener('livewire:initialized', () => {
                if (window.Livewire) {
                    window.Livewire.on('turn-advanced', data => {
                        if (data.turnEndTime) {
                            this.managers.countdownManager.start(data.turnEndTime);
                        } else {
                            this.managers.countdownManager.stop();
                        }
                    });
                }
            });
        }
    }

    /**
     * Livewireコンポーネントの初期化（遅延実行）
     */
    initializeLivewireComponentsDelayed() {
        // Livewire初期化後に実行
        if (window.Livewire) {
            this.initializeLivewireComponents();
        } else {
            document.addEventListener('livewire:initialized', () => {
                // さらに少し遅延して確実にDOM要素が準備されるのを待つ
                setTimeout(() => {
                    this.initializeLivewireComponents();
                }, 500);
            });
        }
    }

    /**
     * Livewireコンポーネントの追加初期化
     */
    initializeLivewireComponents() {
        // ヘッダーコンポーネントのカウントダウン初期化
        this.initializeHeaderCountdown();

        // 参加者コンポーネントのカウントダウン初期化
        this.initializeParticipantsCountdown();
    }

    /**
     * ヘッダーコンポーネントのカウントダウン初期化
     */
    initializeHeaderCountdown() {
        const countdownTextElement = document.getElementById('countdown-timer');

        if (!countdownTextElement) {
            console.warn('⚠️ countdown-timer 要素が見つかりません');
            return;
        }

        if (!this.managers.countdownManager) {
            console.warn('⚠️ countdownManager が初期化されていません');
            return;
        }

        // 既存のカウントダウンが動作している場合は初期表示を設定
        this.syncInitialCountdownState(countdownTextElement);

        // カウントダウンリスナーを登録
        this.managers.countdownManager.addListener(timeData => {
            // wire:loading が表示されている場合は更新しない
            const loadingElement = countdownTextElement.querySelector('[wire\\:loading]');
            const loadingRemoveElement = countdownTextElement.querySelector(
                '[wire\\:loading\\.remove]'
            );

            if (loadingElement) {
                loadingElement.style.display = 'none';
                if (loadingRemoveElement) {
                    loadingRemoveElement.style.display = 'inline';
                }
            }

            if (!timeData.isRunning) {
                const finishedText = document.documentElement.lang === 'ja' ? '終了' : 'Finished';
                countdownTextElement.textContent = finishedText;
                countdownTextElement.classList.remove('text-red-600', 'text-primary');
                return;
            }

            // 時間表示を更新
            countdownTextElement.textContent = `${String(timeData.minutes).padStart(2, '0')}:${String(timeData.seconds).padStart(2, '0')}`;

            // 残り時間に応じてスタイル変更
            if (timeData.isWarning) {
                countdownTextElement.classList.add('text-red-600');
                countdownTextElement.classList.remove('text-primary');
            } else {
                countdownTextElement.classList.add('text-primary');
                countdownTextElement.classList.remove('text-red-600');
            }
        });

        // Livewire変数の監視
        this.setupLivewireWatchers(countdownTextElement);
    }

    /**
     * Livewire変数の監視設定
     */
    setupLivewireWatchers(countdownTextElement) {
        if (!window.Livewire) {
            console.warn('⚠️ Livewire が利用できません');
            return;
        }

        const componentElement = countdownTextElement.closest('[wire\\:id]');

        if (!componentElement) {
            console.warn('⚠️ Livewireコンポーネント要素が見つかりません');
            return;
        }

        const componentId = componentElement.getAttribute('wire:id');
        const component = window.Livewire.find(componentId);

        if (!component) {
            console.warn('⚠️ Livewireコンポーネントが見つかりません');
            return;
        }

        // turnEndTimeの初期値設定
        const turnEndTime = component.get('turnEndTime');

        if (turnEndTime) {
            this.managers.countdownManager.start(turnEndTime);
        } else {
            // turnEndTime がない場合は初期表示
            const loadingRemoveElement = countdownTextElement.querySelector(
                '[wire\\:loading\\.remove]'
            );
            if (loadingRemoveElement) {
                loadingRemoveElement.textContent = '--:--';
            } else {
                countdownTextElement.textContent = '--:--';
            }
        }

        // turnEndTime変更の監視
        component.$watch('turnEndTime', newValue => {
            if (newValue) {
                this.managers.countdownManager.start(newValue);
            } else {
                this.managers.countdownManager.stop();
            }
        });
    }

    /**
     * 参加者コンポーネントのカウントダウン初期化
     */
    initializeParticipantsCountdown() {
        const timeLeftSmall = document.getElementById('time-left-small');

        if (!timeLeftSmall) {
            console.warn('⚠️ time-left-small 要素が見つかりません');
            return;
        }

        if (!this.managers.countdownManager) {
            console.warn('⚠️ countdownManager が初期化されていません（参加者）');
            return;
        }

        // 既存のカウントダウンが動作している場合は初期表示を設定
        this.syncInitialCountdownState(timeLeftSmall);

        // グローバルカウントダウンから時間を取得
        this.managers.countdownManager.addListener(timeData => {
            if (!timeData.isRunning) {
                const finishedText = document.documentElement.lang === 'ja' ? '終了' : 'Finished';
                timeLeftSmall.textContent = finishedText;
                return;
            }

            timeLeftSmall.textContent = `${String(timeData.minutes).padStart(2, '0')}:${String(timeData.seconds).padStart(2, '0')}`;

            if (timeData.isWarning) {
                timeLeftSmall.classList.add('text-red-600', 'font-bold');
            } else {
                timeLeftSmall.classList.remove('text-red-600', 'font-bold');
            }
        });
    }

    /**
     * 初期カウントダウン状態の同期
     */
    syncInitialCountdownState(element) {
        // カウントダウンが既に動作している場合、現在の状態を取得して表示
        const currentState = this.managers.countdownManager.getCurrentState();
        if (currentState && currentState.isRunning) {
            const timeText = `${String(currentState.minutes).padStart(2, '0')}:${String(currentState.seconds).padStart(2, '0')}`;
            element.textContent = timeText;

            if (currentState.isWarning) {
                element.classList.add('text-red-600', 'font-bold');
            }
        } else {
            // カウントダウンが動作していない場合は初期表示
            element.textContent = '--:--';
        }
    }

    /**
     * 早期終了機能の初期化
     */
    initializeEarlyTermination() {
        // グローバル関数として定義（テンプレートから呼び出されるため）
        window.confirmEarlyTermination = () => {
            // AIディベートかどうかの判定（DOM要素から推測）
            const isAiDebate =
                document.querySelector('[data-ai-debate="true"]') !== null ||
                document.querySelector('.ai-debate-indicator') !== null ||
                document.body.dataset.aiDebate === 'true' ||
                window.debateData?.isAiDebate;

            const message = isAiDebate
                ? 'ディベートを早期終了しますか？'
                : 'ディベートの早期終了を提案しますか？相手の同意が必要です。';

            if (confirm(message)) {
                // Livewireコンポーネントのメソッドを呼び出し
                this.triggerEarlyTermination();
            }
        };
    }

    /**
     * 早期終了のトリガー
     */
    triggerEarlyTermination() {
        // 早期終了ボタンがあるコンポーネントを特定
        const earlyTerminationContainer = document.querySelector('[data-ai-debate]');
        if (!earlyTerminationContainer) return;

        const componentElement = earlyTerminationContainer.closest('[wire\\:id]');
        if (!componentElement) return;

        const componentId = componentElement.getAttribute('wire:id');
        if (!componentId || !window.Livewire) return;

        const component = window.Livewire.find(componentId);
        if (component) {
            component.call('requestEarlyTermination');
        }
    }

    /**
     * リソースをクリーンアップ
     */
    cleanup() {
        // 各マネージャーのクリーンアップ
        Object.values(this.managers).forEach(manager => {
            if (manager && typeof manager.cleanup === 'function') {
                manager.cleanup();
            }
        });

        // グローバル参照のクリーンアップ
        delete window.debateCountdown;
        delete window.debateShowManager;
        delete window.confirmEarlyTermination;

        this.isInitialized = false;
    }
}

// DOMContentLoaded または Livewire初期化完了後に自動初期化
document.addEventListener('DOMContentLoaded', () => {
    // 初期化を遅延実行
    setTimeout(() => {
        const debateManager = new DebateShowManager();
        debateManager.initialize();
    }, 300);
});

export default DebateShowManager;
