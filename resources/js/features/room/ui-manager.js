/**
 * ルーム待機ページでのUI操作を管理するクラス
 */
export class RoomUIManager {
    constructor() {
        this.initialize();
    }

    initialize() {
        // グローバル関数として残す必要がある機能を設定
        this.setupGlobalFunctions();
    }

    setupGlobalFunctions() {
        // 退出確認ダイアログ
        window.confirmExit = (event, isCreator) => {
            const message = isCreator
                ? window.translations?.confirmExitCreator ||
                  'Are you sure you want to exit? The room will be closed.'
                : window.translations?.confirmExitParticipant ||
                  'Are you sure you want to exit the room?';

            if (!confirm(message)) {
                event.preventDefault();
                return false;
            }
            return true;
        };

        // フォーマットアコーディオンの開閉処理
        window.toggleFormat = contentId => {
            this.toggleFormat(contentId);
        };
    }

    /**
     * フォーマットアコーディオンの開閉処理
     * @param {string} contentId - 対象となるコンテンツ要素のID
     */
    toggleFormat(contentId) {
        const content = document.getElementById(contentId);
        const icon = document.querySelector('.format-icon');

        if (!content || !icon) {
            console.warn(`toggleFormat: Element not found - contentId: ${contentId}`);
            return;
        }

        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            content.classList.add('opacity-100');
            icon.textContent = 'expand_less';
        } else {
            content.classList.add('opacity-0');
            setTimeout(() => {
                content.classList.add('hidden');
                icon.textContent = 'expand_more';
            }, 200);
            content.classList.remove('opacity-100');
        }
    }
}
