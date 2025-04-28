/**
 * Chat Scroll Module
 * チャットの自動スクロール機能を管理するモジュール
 */
import Logger from '../logger';

document.addEventListener('DOMContentLoaded', function() {
    // const logger = new Logger('ScrollManager');

    // 初期化を遅延
    setTimeout(() => {
        initChatScrollManager();
    }, 300);

    /**
     * チャット自動スクロール管理機能
     */
    function initChatScrollManager() {
        // 正しいチャットコンテナを取得
        const mainContainer = document.querySelector('#chat-container');
        if (!mainContainer) {
            return;
        }

        // 実際のメッセージを含む内部コンテナを取得
        let chatContainer = mainContainer.querySelector('.flex-1.overflow-y-auto.p-4.space-y-4');
        if (!chatContainer) {
            // バックアップ: 内部のoverflowを持つ要素を探す
            chatContainer = mainContainer.querySelector('.overflow-y-auto');
            if (!chatContainer) {
                // フォールバック: メインコンテナを使用
                chatContainer = mainContainer;
            }
        }

        const newMessageNotification = document.getElementById('new-message-notification');
        let isUserScrollingUp = false;
        let manualScrollDetected = false;
        let lastScrollTop = 0;
        let isInitialized = false;
        let hasOverflow = false; // スクロールが必要かどうかを追跡


        // スクロールが必要かチェック
        const checkIfOverflowing = () => {
            hasOverflow = chatContainer.scrollHeight > chatContainer.clientHeight;
            return hasOverflow;
        };

        // 最下部へスクロールする関数
        const scrollToBottom = (smooth = true) => {
            if (!chatContainer) return;

            // スクロールが必要かチェック
            if (!checkIfOverflowing()) {
                // スクロールは不要だが、状態はリセット
                resetScrollState();
                return;
            }

            chatContainer.scrollTo({
                top: chatContainer.scrollHeight,
                behavior: smooth ? 'smooth' : 'instant'
            });

            resetScrollState();

            // スクロール位置の再確認
            setTimeout(() => {
                checkIfOverflowing();
                lastScrollTop = chatContainer.scrollTop;
            }, 50);
        };

        // 最下部にいるかチェックする
        const isNearBottom = () => {
            if (!chatContainer) return true;
            if (!checkIfOverflowing()) return true; // オーバーフローがなければ常に最下部と判断

            const threshold = 100;
            const scrollBottom = chatContainer.scrollHeight - chatContainer.scrollTop - chatContainer.clientHeight;
            return scrollBottom < threshold;
        };

        // 通知表示
        const showNewMessageNotification = () => {
            if (!newMessageNotification) return;
            if (!checkIfOverflowing()) return; // スクロールの必要がなければ通知しない

            newMessageNotification.classList.remove('hidden');
            clearTimeout(newMessageNotification.timer);
            newMessageNotification.timer = setTimeout(hideNewMessageNotification, 5000);
        };

        // 通知非表示
        const hideNewMessageNotification = () => {
            if (!newMessageNotification) return;
            newMessageNotification.classList.add('hidden');
            clearTimeout(newMessageNotification.timer);
        };

        // スクロール状態をリセット
        const resetScrollState = () => {
            isUserScrollingUp = false;
            manualScrollDetected = false;
        };

        // チャットコンテンツの高さ変更を監視する関数
        const monitorContentHeight = () => {
            let lastHeight = chatContainer.scrollHeight;

            setInterval(() => {
                if (chatContainer.scrollHeight !== lastHeight) {
                    lastHeight = chatContainer.scrollHeight;

                    // 高さ変更時に自動スクロールを検討
                    if (!isUserScrollingUp || isNearBottom()) {
                        scrollToBottom();
                    }
                }
            }, 500);
        };


        // 初期スクロール
        setTimeout(() => {
            checkIfOverflowing(); // 初期状態を確認
            scrollToBottom(true);
            isInitialized = true;
            monitorContentHeight(); // コンテンツ高さの監視を開始
        }, 500);

        // スクロール検出
        chatContainer.addEventListener('scroll', () => {
            if (!isInitialized || !hasOverflow) return;

            // 10px以上の変化で手動スクロールと判断
            if (Math.abs(chatContainer.scrollTop - lastScrollTop) > 10) {
                manualScrollDetected = true;
            }

            lastScrollTop = chatContainer.scrollTop;

            clearTimeout(scrollTimeout);
            var scrollTimeout = setTimeout(() => {
                if (!isNearBottom() && manualScrollDetected) {
                    isUserScrollingUp = true;
                } else if (isNearBottom()) {
                    resetScrollState();
                    hideNewMessageNotification();
                }
            }, 150);
        });

        // メッセージイベント処理
        const handleMessageEvent = (isSelf = false) => {
            setTimeout(() => {
                // オーバーフロー状態を再確認
                checkIfOverflowing();


                if (!hasOverflow) {
                    return;
                }

                if (isSelf) {
                    scrollToBottom();
                } else if (!isUserScrollingUp || isNearBottom()) {
                    scrollToBottom();
                } else {
                    showNewMessageNotification();
                }
            }, 300); // メッセージ処理の遅延
        };

        // Livewireイベントリスナー
        if (window.Livewire) {

            Livewire.on('message-received', () => {
                handleMessageEvent(false);
            });

            Livewire.on('message-sent', () => {
                handleMessageEvent(true);
            });
        } else {
            // logger.warn('Livewire not available');
        }

        // 通知クリックでスクロール
        if (newMessageNotification) {
            newMessageNotification.addEventListener('click', () => {
                scrollToBottom();
                hideNewMessageNotification();
            });
        }

        // DOM変更の監視
        const observer = new MutationObserver((mutations) => {
            let hasContentChanges = false;

            mutations.forEach(mutation => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    hasContentChanges = true;
                }
            });

            if (hasContentChanges) {
                checkIfOverflowing(); // 変更後にオーバーフロー状態を確認
            }
        });

        observer.observe(chatContainer, {
            childList: true,
            subtree: true
        });
    }
});
