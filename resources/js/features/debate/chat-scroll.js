import Logger from '../../services/logger.js';

/**
 * チャットスクロール管理
 * チャットの自動スクロール機能を管理
 */
class ChatScrollManager {
    constructor() {
        this.logger = new Logger('ChatScrollManager');
        this.mainContainer = null;
        this.chatContainer = null;
        this.isUserScrollingUp = false;
        this.manualScrollDetected = false;
        this.lastScrollTop = 0;
        this.isInitialized = false;
        this.hasOverflow = false;
        this.scrollTimeout = null;
        this.observer = null;
    }

    /**
     * チャットスクロール管理を初期化
     */
    initialize() {
        // 初期化を遅延
        setTimeout(() => {
            this.initChatScrollManager();
        }, 300);
    }

    /**
     * チャット自動スクロール管理機能を初期化
     */
    initChatScrollManager() {
        // 正しいチャットコンテナを取得
        this.mainContainer = document.querySelector('#chat-container');
        if (!this.mainContainer) {
            this.logger.error('Main chat container not found. Auto-scrolling disabled.');
            return;
        }

        // 実際のメッセージを含む内部コンテナを取得
        // Livewire.Debates.Chat コンポーネント内のスクロール可能な領域を取得
        this.chatContainer = this.mainContainer.querySelector(
            '.flex-1.overflow-y-auto.p-4.space-y-4'
        );
        if (!this.chatContainer) {
            // バックアップ: 内部のoverflowを持つ要素を探す
            this.chatContainer = this.mainContainer.querySelector('.overflow-y-auto');
            if (!this.chatContainer) {
                // フォールバック: メインコンテナを使用
                this.logger.warn('Internal scroll container not found, using main container');
                this.chatContainer = this.mainContainer;
            }
        }

        this.setupEventListeners();
        this.initializeScrolling();
        this.monitorContentHeight();
    }

    /**
     * イベントリスナーを設定
     */
    setupEventListeners() {
        // スクロール検出
        this.chatContainer.addEventListener('scroll', () => {
            this.handleScroll();
        });

        // Livewireイベントリスナー
        if (window.Livewire) {
            window.Livewire.on('message-received', () => {
                this.handleMessageEvent(false);
            });

            window.Livewire.on('message-sent', () => {
                this.handleMessageEvent(true);
            });
        } else {
            this.logger.warn('Livewire not available');
        }

        // DOM変更の監視
        this.observer = new MutationObserver(mutations => {
            let hasContentChanges = false;

            mutations.forEach(mutation => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    hasContentChanges = true;
                }
            });

            if (hasContentChanges) {
                this.checkIfOverflowing(); // 変更後にオーバーフロー状態を確認
            }
        });

        this.observer.observe(this.chatContainer, {
            childList: true,
            subtree: true,
        });
    }

    /**
     * 初期スクロール設定
     */
    initializeScrolling() {
        // 初期スクロール
        setTimeout(() => {
            this.checkIfOverflowing(); // 初期状態を確認
            this.scrollToBottom(true); // アニメーションを有効に
            this.isInitialized = true;
        }, 500);
    }

    /**
     * スクロールが必要かチェック
     */
    checkIfOverflowing() {
        this.hasOverflow = this.chatContainer.scrollHeight > this.chatContainer.clientHeight;
        return this.hasOverflow;
    }

    /**
     * 最下部へスクロールする関数
     */
    scrollToBottom(smooth = true) {
        if (!this.chatContainer) return;

        // スクロールが必要かチェック
        if (!this.checkIfOverflowing()) {
            // スクロールは不要だが、状態はリセット
            this.resetScrollState();
            return;
        }

        this.chatContainer.scrollTo({
            top: this.chatContainer.scrollHeight,
            behavior: smooth ? 'smooth' : 'instant',
        });

        this.resetScrollState();

        // スクロール位置の再確認
        setTimeout(() => {
            this.checkIfOverflowing();
            this.lastScrollTop = this.chatContainer.scrollTop;
        }, 50);
    }

    /**
     * 最下部にいるかチェックする関数
     */
    isNearBottom() {
        if (!this.chatContainer) return true;
        if (!this.checkIfOverflowing()) return true; // オーバーフローがなければ常に最下部と判断

        const threshold = 100;
        const scrollBottom =
            this.chatContainer.scrollHeight -
            this.chatContainer.scrollTop -
            this.chatContainer.clientHeight;
        return scrollBottom < threshold;
    }

    /**
     * スクロール状態をリセット
     */
    resetScrollState() {
        this.isUserScrollingUp = false;
        this.manualScrollDetected = false;
    }

    /**
     * スクロール処理
     */
    handleScroll() {
        if (!this.isInitialized || !this.hasOverflow) return;

        // 10px以上の変化で手動スクロールと判断
        if (Math.abs(this.chatContainer.scrollTop - this.lastScrollTop) > 10) {
            this.manualScrollDetected = true;
        }

        this.lastScrollTop = this.chatContainer.scrollTop;

        clearTimeout(this.scrollTimeout);
        this.scrollTimeout = setTimeout(() => {
            if (!this.isNearBottom() && this.manualScrollDetected) {
                this.isUserScrollingUp = true;
            } else if (this.isNearBottom()) {
                this.resetScrollState();
            }
        }, 150);
    }

    /**
     * メッセージイベント処理
     */
    handleMessageEvent(isSelf = false) {
        setTimeout(() => {
            // オーバーフロー状態を再確認
            this.checkIfOverflowing();

            if (!this.hasOverflow) {
                return;
            }

            this.scrollToBottom();
        }, 300); // メッセージ処理の遅延
    }

    /**
     * チャットコンテンツの高さ変更を監視する関数
     */
    monitorContentHeight() {
        let lastHeight = this.chatContainer.scrollHeight;

        setInterval(() => {
            if (this.chatContainer.scrollHeight !== lastHeight) {
                lastHeight = this.chatContainer.scrollHeight;

                // 高さ変更時に自動スクロールを検討
                if (!this.isUserScrollingUp || this.isNearBottom()) {
                    this.scrollToBottom();
                }
            }
        }, 500);
    }

    /**
     * リソースをクリーンアップ
     */
    cleanup() {
        if (this.observer) {
            this.observer.disconnect();
        }

        if (this.scrollTimeout) {
            clearTimeout(this.scrollTimeout);
        }
    }
}

export default ChatScrollManager;
