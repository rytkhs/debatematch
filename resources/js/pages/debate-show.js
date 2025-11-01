import CountdownManager from '../features/debate/countdown-manager.js';
import CountdownTimer from '../components/countdown-timer.js';
import DebateEventHandler from '../features/debate/event-handler.js';
import ChatScrollManager from '../features/debate/chat-scroll.js';
import DebateUIManager from '../features/debate/ui-manager.js';
import AudioHandler from '../features/debate/audio-handler.js';
import InputAreaManager from '../features/debate/input-area.js';
import HeartbeatService from '../services/heartbeat.js';

/**
 * ディベートページの統合管理クラス
 * すべてのディベート関連機能を初期化・統合管理
 */
class DebateShowManager {
    constructor(debateData) {
        this.managers = {};
        this.isInitialized = false;
        this.initializationTimeout = null;
        this.heartbeatService = null;
        this.turnAdvancedListener = null;
        this.turnAdvancedCleanup = null;
        this.debateData = debateData;
        // MutationObserver for DOM changes
        this.mutationObserver = null;
        this.livewireUpdateListener = null;
    }

    /**
     * ディベートページを初期化
     */
    initialize() {
        if (this.isInitialized) return;

        try {
            // デバッグデータの確認
            if (typeof this.debateData === 'undefined') {
                console.error('this.debateData is not available');
                return;
            }

            // 既存のグローバルマネージャーをクリーンアップ
            if (window.debateShowManager && window.debateShowManager !== this) {
                window.debateShowManager.cleanup();
            }

            // 各機能の初期化（エラーハンドリング付き）
            this.safeInitialize('Countdown', () => this.initializeCountdown());
            this.safeInitialize('EventHandler', () => this.initializeEventHandler());
            this.safeInitialize('Heartbeat', () => this.initializeHeartbeat());
            this.safeInitialize('ChatScroll', () => this.initializeChatScroll());
            this.safeInitialize('UIManager', () => this.initializeUIManager());
            this.safeInitialize('AudioHandler', () => this.initializeAudioHandler());
            this.safeInitialize('InputArea', () => this.initializeInputArea());

            // グローバル参照設定（後方互換性のため）
            this.setupGlobalReferences();

            // 早期終了機能の初期化
            this.initializeEarlyTermination();

            // MutationObserverの初期化
            this.initializeMutationObserver();

            // Livewireコンポーネントの初期化（遅延実行）
            this.initializeLivewireComponentsDelayed();

            this.isInitialized = true;
        } catch (error) {
            console.error('DebateShowManager initialization failed:', error);
            // 部分的な初期化でも継続
            this.isInitialized = true;
        }
    }

    /**
     * 安全な初期化ヘルパー
     */
    safeInitialize(componentName, initFunction) {
        try {
            initFunction();
        } catch (error) {
            console.error(`${componentName} initialization failed:`, error);
            // 個別の初期化失敗でも全体は継続
        }
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
        this.managers.eventHandler = new DebateEventHandler(this.debateData.debateId);
    }

    /**
     * ハートビートサービスを初期化
     */
    initializeHeartbeat() {
        this.heartbeatService = new HeartbeatService({
            contextType: 'debate',
            contextId: this.debateData.debateId,
        });
        setTimeout(() => this.heartbeatService.start(), 30000);
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
        this.managers.audioHandler = new AudioHandler(this.debateData);
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

        // Livewireイベントリスナーを登録
        this.setupLivewireEventListeners();
    }

    /**
     * Livewireイベントリスナーを設定
     */
    setupLivewireEventListeners() {
        if (!window.Livewire) return;

        // 既存のリスナーがあれば適切にクリーンアップ（重複登録防止）
        if (this.turnAdvancedCleanup) {
            this.turnAdvancedCleanup();
            this.turnAdvancedCleanup = null;
        }

        this.turnAdvancedListener = data => {
            if (!this.managers.countdownManager) return;
            if (data.turnEndTime) {
                this.managers.countdownManager.start(data.turnEndTime);
            } else {
                this.managers.countdownManager.stop();
            }
        };

        // Livewire.on()のクリーンアップ関数を保存
        this.turnAdvancedCleanup = window.Livewire.on('turn-advanced', this.turnAdvancedListener);
    }

    /**
     * Livewireコンポーネントの初期化（遅延実行）
     */
    initializeLivewireComponentsDelayed() {
        // Livewire初期化後に実行
        if (window.Livewire) {
            this.initializeLivewireComponents();
            this.setupLivewireUpdateListener();
        } else {
            document.addEventListener('livewire:initialized', () => {
                // さらに少し遅延して確実にDOM要素が準備されるのを待つ
                setTimeout(() => {
                    this.initializeLivewireComponents();
                    this.setupLivewireUpdateListener();
                }, 500);
            });
        }
    }

    /**
     * Livewire更新イベントリスナーを設定
     */
    setupLivewireUpdateListener() {
        if (!window.Livewire) return;

        // 既存のリスナーをクリーンアップ
        if (this.livewireUpdateListener) {
            this.livewireUpdateListener();
            this.livewireUpdateListener = null;
        }

        // Livewire更新時の再初期化
        this.livewireUpdateListener = window.Livewire.hook('morph.updated', () => {
            // DOM更新後に短い遅延を置いて再初期化
            setTimeout(() => {
                this.reinitializeCountdownElements();
            }, 100);
        });
    }

    /**
     * カウントダウン要素の再初期化
     */
    reinitializeCountdownElements() {
        try {
            // ヘッダーのカウントダウンを再初期化
            const countdownElement = document.getElementById('countdown-timer');
            if (countdownElement && !countdownElement.dataset.initialized) {
                this.initializeHeaderCountdown();
                countdownElement.dataset.initialized = 'true';
            }

            // 参加者のカウントダウンを再初期化
            const participantElement = document.getElementById('time-left-small');
            if (participantElement && !participantElement.dataset.initialized) {
                this.initializeParticipantsCountdown();
                participantElement.dataset.initialized = 'true';
            }
        } catch (error) {
            console.error('カウントダウン要素の再初期化エラー:', error);
        }
    }

    /**
     * MutationObserverを初期化してDOM変更を監視
     */
    initializeMutationObserver() {
        if (!window.MutationObserver) return;

        this.mutationObserver = new MutationObserver(mutations => {
            let shouldReinitialize = false;

            mutations.forEach(mutation => {
                // 新しいノードが追加された場合
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === window.Node.ELEMENT_NODE) {
                        // countdown-timer要素が追加された場合
                        if (
                            node.id === 'countdown-timer' ||
                            (node.querySelector && node.querySelector('#countdown-timer'))
                        ) {
                            shouldReinitialize = true;
                        }
                        // time-left-small要素が追加された場合
                        if (
                            node.id === 'time-left-small' ||
                            (node.querySelector && node.querySelector('#time-left-small'))
                        ) {
                            shouldReinitialize = true;
                        }
                    }
                });
            });

            if (shouldReinitialize) {
                // 少し遅延させて確実にDOMが準備された後に実行
                setTimeout(() => {
                    this.reinitializeCountdownElements();
                }, 50);
            }
        });

        // ヘッダー要素を監視対象に設定
        const headerElement = document.querySelector('header');
        if (headerElement) {
            this.mutationObserver.observe(headerElement, {
                childList: true,
                subtree: true,
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

        // 重複初期化を防ぐ
        if (countdownTextElement.dataset.initialized === 'true') {
            return;
        }

        // 既存のカウントダウンが動作している場合は初期表示を設定
        this.syncInitialCountdownState(countdownTextElement);

        // カウントダウンリスナーを登録
        const listenerFunction = timeData => {
            // 要素が存在しない場合は処理をスキップ
            if (!document.getElementById('countdown-timer')) {
                return;
            }

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
        };

        this.managers.countdownManager.addListener(listenerFunction);

        // 初期化完了をマーク
        countdownTextElement.dataset.initialized = 'true';
        countdownTextElement.dataset.listenerId = Date.now().toString();

        // Livewire変数の監視（エラーハンドリング強化）
        this.setupLivewireWatchers(countdownTextElement);
    }

    /**
     * Livewire変数の監視設定（エラーハンドリング強化）
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

        try {
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

            // turnEndTime変更の監視（エラーハンドリング付き）
            component.$watch('turnEndTime', newValue => {
                try {
                    if (newValue) {
                        this.managers.countdownManager.start(newValue);
                    } else {
                        this.managers.countdownManager.stop();
                    }
                } catch (error) {
                    console.error('turnEndTime監視エラー:', error);
                    // フォールバック処理
                    if (countdownTextElement) {
                        countdownTextElement.textContent = '--:--';
                    }
                }
            });
        } catch (error) {
            console.error('Livewire変数監視の設定エラー:', error);
            // フォールバック処理
            if (countdownTextElement) {
                countdownTextElement.textContent = '--:--';
            }
        }
    }

    /**
     * 参加者コンポーネントのカウントダウン初期化
     */
    initializeParticipantsCountdown() {
        const timeLeftSmall = document.getElementById('time-left-small');

        if (!timeLeftSmall) {
            return;
        }

        if (!this.managers.countdownManager) {
            console.warn('⚠️ countdownManager が初期化されていません（参加者）');
            return;
        }

        // 重複初期化を防ぐ
        if (timeLeftSmall.dataset.initialized === 'true') {
            return;
        }

        // 既存のカウントダウンが動作している場合は初期表示を設定
        this.syncInitialCountdownState(timeLeftSmall);

        // カウントダウンから時間を取得
        const listenerFunction = timeData => {
            // 要素が存在しない場合は処理をスキップ
            if (!document.getElementById('time-left-small')) {
                return;
            }

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
        };

        this.managers.countdownManager.addListener(listenerFunction);

        // 初期化完了をマーク
        timeLeftSmall.dataset.initialized = 'true';
        timeLeftSmall.dataset.listenerId = Date.now().toString();
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
                this.debateData?.isAiDebate;

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
        if (!this.isInitialized) return;

        // 初期化タイムアウトをクリア
        if (this.initializationTimeout) {
            clearTimeout(this.initializationTimeout);
            this.initializationTimeout = null;
        }

        // MutationObserverのクリーンアップ
        if (this.mutationObserver) {
            this.mutationObserver.disconnect();
            this.mutationObserver = null;
        }

        // Livewire更新リスナーのクリーンアップ
        if (this.livewireUpdateListener) {
            this.livewireUpdateListener();
            this.livewireUpdateListener = null;
        }

        // 各マネージャーのクリーンアップ
        Object.values(this.managers).forEach(manager => {
            if (manager && typeof manager.cleanup === 'function') {
                try {
                    manager.cleanup();
                } catch (error) {
                    console.error('Error cleaning up manager:', error);
                }
            }
        });

        // ハートビートサービスのクリーンアップ
        if (this.heartbeatService) {
            this.heartbeatService.stop();
        }

        // マネージャー参照をクリア
        this.managers = {};

        // DOM要素の初期化フラグをクリア
        const countdownElement = document.getElementById('countdown-timer');
        if (countdownElement) {
            delete countdownElement.dataset.initialized;
            delete countdownElement.dataset.listenerId;
        }

        const participantElement = document.getElementById('time-left-small');
        if (participantElement) {
            delete participantElement.dataset.initialized;
            delete participantElement.dataset.listenerId;
        }

        // グローバル参照のクリーンアップ
        if (window.debateCountdown === this.managers.countdownManager) {
            delete window.debateCountdown;
        }
        if (window.debateShowManager === this) {
            delete window.debateShowManager;
        }
        if (window.confirmEarlyTermination) {
            delete window.confirmEarlyTermination;
        }

        // Livewireイベントリスナーをクリーンアップ
        if (this.turnAdvancedCleanup) {
            this.turnAdvancedCleanup();
            this.turnAdvancedCleanup = null;
        }
        this.turnAdvancedListener = null;

        this.isInitialized = false;
    }
}

// --- NEW INITIALIZATION LOGIC ---
let debateManager = null;

window.initializeDebatePage = data => {
    // If a manager instance exists, clean it up first.
    if (debateManager) {
        debateManager.cleanup();
    }

    // Create and initialize a new manager instance.
    // Pass the entire data object from x-init.
    debateManager = new DebateShowManager(data);
    debateManager.initialize();
};

window.cleanupDebatePage = () => {
    if (debateManager) {
        debateManager.cleanup();
        debateManager = null;
    }
};

// デバッグ用のヘルパー関数
window.debugCountdownTimer = () => {
    if (!debateManager || !debateManager.managers.countdownManager) {
        console.log('DebateManager または CountdownManager が初期化されていません');
        return;
    }

    const manager = debateManager.managers.countdownManager;
    const debugInfo = manager.getDebugInfo();

    console.group('🔍 カウントダウンタイマー デバッグ情報');
    console.log('基本情報:', debugInfo);

    // DOM要素の状態
    const countdownElement = document.getElementById('countdown-timer');
    const participantElement = document.getElementById('time-left-small');

    console.log('DOM要素の状態:', {
        countdownElement: {
            exists: !!countdownElement,
            initialized: countdownElement?.dataset.initialized,
            textContent: countdownElement?.textContent,
            listenerId: countdownElement?.dataset.listenerId,
        },
        participantElement: {
            exists: !!participantElement,
            initialized: participantElement?.dataset.initialized,
            textContent: participantElement?.textContent,
            listenerId: participantElement?.dataset.listenerId,
        },
    });

    // Livewire接続状態
    console.log('Livewire状態:', {
        livewireAvailable: !!window.Livewire,
        echoAvailable: !!window.Echo,
        pusherConnected: window.Echo?.connector?.pusher?.connection?.state,
    });

    // マネージャーの状態
    console.log('DebateManager状態:', {
        isInitialized: debateManager.isInitialized,
        hasCountdownManager: !!debateManager.managers.countdownManager,
        hasEventHandler: !!debateManager.managers.eventHandler,
        managersCount: Object.keys(debateManager.managers).length,
    });

    console.groupEnd();

    return debugInfo;
};

// 本番環境での問題検出用
window.checkCountdownHealth = () => {
    const health = {
        status: 'healthy',
        issues: [],
        timestamp: new Date().toISOString(),
    };

    // DOM要素チェック
    const countdownElement = document.getElementById('countdown-timer');
    if (!countdownElement) {
        health.issues.push('countdown-timer要素が見つかりません');
        health.status = 'error';
    } else if (!countdownElement.dataset.initialized) {
        health.issues.push('countdown-timer要素が初期化されていません');
        health.status = 'warning';
    }

    // マネージャーチェック
    if (!debateManager) {
        health.issues.push('DebateManagerが初期化されていません');
        health.status = 'error';
    } else if (!debateManager.managers.countdownManager) {
        health.issues.push('CountdownManagerが初期化されていません');
        health.status = 'error';
    }

    // WebSocket接続チェック
    if (!window.Echo) {
        health.issues.push('Echo(WebSocket)が利用できません');
        health.status = 'warning';
    } else if (window.Echo.connector?.pusher?.connection?.state !== 'connected') {
        health.issues.push(
            `WebSocket接続状態が不正: ${window.Echo.connector?.pusher?.connection?.state}`
        );
        health.status = 'warning';
    }

    // Livewireチェック
    if (!window.Livewire) {
        health.issues.push('Livewireが利用できません');
        health.status = 'error';
    }

    console.log('🏥 カウントダウンタイマー ヘルスチェック:', health);
    return health;
};

// 自動ヘルスチェック（本番環境のみ）
if (import.meta.env.PROD) {
    setInterval(() => {
        const health = window.checkCountdownHealth();
        if (health.status === 'error') {
            console.error(
                '⚠️ カウントダウンタイマーにクリティカルな問題が検出されました:',
                health.issues
            );

            // 自動復旧を試行
            if (
                debateManager &&
                typeof debateManager.reinitializeCountdownElements === 'function'
            ) {
                console.log('🔄 自動復旧を試行します...');
                debateManager.reinitializeCountdownElements();
            }
        }
    }, 30000); // 30秒間隔でチェック
}

// Add a listener to clean up before Livewire navigates away
document.addEventListener('livewire:navigating', () => {
    if (window.cleanupDebatePage) {
        window.cleanupDebatePage();
    }
});

export default DebateShowManager;
