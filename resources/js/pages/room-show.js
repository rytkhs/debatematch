import { RoomEventHandler } from '../features/room/event-handler.js';
import { RoomUIManager } from '../features/room/ui-manager.js';
import HeartbeatService from '../services/heartbeat.js';

/**
 * ルーム待機ページのメインマネージャークラス
 */
class RoomShowManager {
    constructor(options) {
        this.roomId = options.roomId;
        this.userId = options.authUserId;
        this.eventHandler = null;
        this.uiManager = null;
        this.heartbeatService = null;

        this.initialize();
    }

    initialize() {
        this.uiManager = new RoomUIManager();
        this.eventHandler = new RoomEventHandler(this.roomId, this.userId);
        this.initializeHeartbeat();
        this.initializeCopyButton();
    }

    initializeHeartbeat() {
        this.heartbeatService = new HeartbeatService({
            contextType: 'room',
            contextId: this.roomId,
        });
        setTimeout(() => this.heartbeatService.start(), 30000);
    }

    /**
     * コピーボタンの成功フィードバックを表示
     * @param {HTMLElement} button - コピーボタン要素
     */
    showSuccessFeedback(button) {
        const icon = button.querySelector('.copy-icon');
        const text = button.querySelector('.button-text');

        if (icon) {
            icon.textContent = 'check';
        }
        if (text) {
            text.textContent = button.dataset.copiedText;
        }
        button.classList.add('bg-green-100', 'text-green-700');
        button.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    }

    /**
     * コピーボタンのエラーフィードバックを表示
     * @param {HTMLElement} button - コピーボタン要素
     */
    showErrorFeedback(button) {
        const icon = button.querySelector('.copy-icon');
        const text = button.querySelector('.button-text');

        if (icon) {
            icon.textContent = 'error';
        }
        if (text) {
            text.textContent = button.dataset.errorText;
        }
        button.classList.add('bg-red-100', 'text-red-700');
        button.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    }

    /**
     * コピーボタンを元の状態にリセット
     * @param {HTMLElement} button - コピーボタン要素
     */
    resetButton(button) {
        const icon = button.querySelector('.copy-icon');
        const text = button.querySelector('.button-text');

        if (icon) {
            icon.textContent = 'content_copy';
        }
        if (text) {
            text.textContent = button.dataset.originalText;
        }
        button.classList.remove('bg-green-100', 'text-green-700', 'bg-red-100', 'text-red-700');
        button.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
        button.disabled = false;
    }

    /**
     * コピーボタンの初期化
     */
    initializeCopyButton() {
        const copyButton = document.getElementById('copy-room-url-btn');

        if (!copyButton) {
            return;
        }

        // コピーボタンのクリックイベント
        copyButton.addEventListener('click', async () => {
            // ボタンを無効化（連続クリック防止）
            copyButton.disabled = true;

            // ルームURLを取得（Blade側で完全なURLを生成している）
            const fullUrl = `${copyButton.dataset.roomUrl}/preview`;

            try {
                // クリップボードAPIを使用してコピー
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(fullUrl);
                    this.showSuccessFeedback(copyButton);
                } else {
                    // フォールバック: テキストエリアを使用
                    const textArea = document.createElement('textarea');
                    textArea.value = fullUrl;
                    textArea.style.position = 'fixed';
                    textArea.style.left = '-999999px';
                    document.body.appendChild(textArea);
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        this.showSuccessFeedback(copyButton);
                    } catch (err) {
                        this.showErrorFeedback(copyButton);
                    }
                    document.body.removeChild(textArea);
                }
            } catch (error) {
                console.error('Copy failed:', error);
                this.showErrorFeedback(copyButton);
            }

            // 2秒後にボタンを元に戻す
            setTimeout(() => {
                this.resetButton(copyButton);
            }, 2000);
        });
    }

    cleanup() {
        if (this.eventHandler) {
            this.eventHandler.cleanup();
        }
        if (this.heartbeatService) {
            this.heartbeatService.stop();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (!window.roomData) {
        console.error('roomData is not defined');
        return;
    }
    const roomManager = new RoomShowManager(window.roomData);

    window.addEventListener('beforeunload', () => {
        roomManager.cleanup();
    });
});
