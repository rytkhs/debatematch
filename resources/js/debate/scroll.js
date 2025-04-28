/**
 * Chat Scroll Module
 * チャットの自動スクロール機能を管理するモジュール
 */
document.addEventListener('DOMContentLoaded', function() {
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
            console.error('Main chat container not found. Auto-scrolling disabled.');
            return;
        }

        // 実際のメッセージを含む内部コンテナを取得
        // Livewire.Debates.Chat コンポーネント内のスクロール可能な領域を取得
        let chatContainer = mainContainer.querySelector('.flex-1.overflow-y-auto.p-4.space-y-4');
        if (!chatContainer) {
            // バックアップ: 内部のoverflowを持つ要素を探す
            chatContainer = mainContainer.querySelector('.overflow-y-auto');
            if (!chatContainer) {
                // フォールバック: メインコンテナを使用
                console.warn('Internal scroll container not found, using main container');
                chatContainer = mainContainer;
            }
        }

        // const newMessageNotification = document.getElementById('new-message-notification');
        let isUserScrollingUp = false;
        let manualScrollDetected = false;
        let lastScrollTop = 0;
        let isInitialized = false;
        let hasOverflow = false; // スクロールが必要かどうかを追跡

        console.log('Chat Scroll Manager initialized with container:', chatContainer);
        console.log(`Initial container state: scrollTop=${chatContainer.scrollTop}, scrollHeight=${chatContainer.scrollHeight}, clientHeight=${chatContainer.clientHeight}`);

        // スクロールが必要かチェック
        const checkIfOverflowing = () => {
            hasOverflow = chatContainer.scrollHeight > chatContainer.clientHeight;
            console.log(`Overflow check: scrollHeight=${chatContainer.scrollHeight}, clientHeight=${chatContainer.clientHeight}, hasOverflow=${hasOverflow}`);
            return hasOverflow;
        };

        // 最下部へスクロールする関数
        const scrollToBottom = (smooth = true) => {
            if (!chatContainer) return;

            // スクロールが必要かチェック
            if (!checkIfOverflowing()) {
                console.log('No overflow, scrolling not needed');
                // スクロールは不要だが、状態はリセット
                resetScrollState();
                return;
            }

            console.log(`Scrolling to bottom. Smooth: ${smooth}, Height: ${chatContainer.scrollHeight}`);
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

        // 最下部にいるかチェックする関数
        const isNearBottom = () => {
            if (!chatContainer) return true;
            if (!checkIfOverflowing()) return true; // オーバーフローがなければ常に最下部と判断

            const threshold = 100;
            const scrollBottom = chatContainer.scrollHeight - chatContainer.scrollTop - chatContainer.clientHeight;
            console.log(`Scroll check: scrollTop=${chatContainer.scrollTop}, scrollHeight=${chatContainer.scrollHeight}, clientHeight=${chatContainer.clientHeight}, scrollBottom=${scrollBottom}, nearBottom=${scrollBottom < threshold}`);
            return scrollBottom < threshold;
        };

        // スクロール状態をリセット
        const resetScrollState = () => {
            isUserScrollingUp = false;
            manualScrollDetected = false;
            console.log('Scroll state reset');
        };

        // チャットコンテンツの高さ変更を監視する関数
        const monitorContentHeight = () => {
            let lastHeight = chatContainer.scrollHeight;

            setInterval(() => {
                if (chatContainer.scrollHeight !== lastHeight) {
                    console.log(`Content height changed: ${lastHeight} -> ${chatContainer.scrollHeight}`);
                    lastHeight = chatContainer.scrollHeight;

                    // 高さ変更時に自動スクロールを検討
                    if (!isUserScrollingUp || isNearBottom()) {
                        console.log('Content height changed, auto-scrolling');
                        scrollToBottom();
                    }
                }
            }, 500);
        };

        // --- イベントリスナー ---

        // 初期スクロール
        setTimeout(() => {
            console.log('Forcing initial scroll to bottom');
            checkIfOverflowing(); // 初期状態を確認
            scrollToBottom(true); // false から true に変更してアニメーションを有効に
            isInitialized = true;
            console.log('Initial scroll to bottom complete with animation');
            monitorContentHeight(); // コンテンツ高さの監視を開始
        }, 500);

        // スクロール検出
        chatContainer.addEventListener('scroll', () => {
            if (!isInitialized || !hasOverflow) return;

            // 10px以上の変化で手動スクロールと判断
            if (Math.abs(chatContainer.scrollTop - lastScrollTop) > 10) {
                manualScrollDetected = true;
                console.log(`Manual scroll detected: ${lastScrollTop} -> ${chatContainer.scrollTop}`);
            }

            lastScrollTop = chatContainer.scrollTop;

            clearTimeout(scrollTimeout);
            var scrollTimeout = setTimeout(() => {
                if (!isNearBottom() && manualScrollDetected) {
                    isUserScrollingUp = true;
                    console.log('User scrolled up - not near bottom.');
                } else if (isNearBottom()) {
                    resetScrollState();
                    console.log('User scrolled to bottom.');
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

                scrollToBottom();
            }, 300); // メッセージ処理の遅延
        };

        // Livewireイベントリスナー
        if (window.Livewire) {
            console.log('Setting up Livewire event listeners');

            Livewire.on('message-received', () => {
                console.log('Event received: message-received');
                handleMessageEvent(false);
            });

            Livewire.on('message-sent', () => {
                console.log('Event received: message-sent');
                handleMessageEvent(true);
            });
        } else {
            console.warn('Livewire not available');
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
                console.log('Chat content changed via DOM mutation');
                checkIfOverflowing(); // 変更後にオーバーフロー状態を確認
            }
        });

        observer.observe(chatContainer, {
            childList: true,
            subtree: true
        });
    }
});
