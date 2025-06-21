/**
 * グリッド/リストビューの切り替えを管理するクラス
 */
export class ViewSwitcher {
    constructor(containerElement) {
        if (!containerElement) return;
        this.container = containerElement;
        this.buttons = this.container.querySelectorAll('[data-view-toggle]');
        this.panels = {};

        // パネルを取得（親要素から検索）
        this.container.parentElement.querySelectorAll('[data-view-panel]').forEach(panel => {
            this.panels[panel.dataset.viewPanel] = panel;
        });

        this.storageKey = 'debateRecordsView';
        this.activeClass = ['bg-blue-100', 'text-blue-700'];
        this.inactiveClass = ['text-gray-600', 'hover:text-gray-800'];

        this.init();
    }

    init() {
        this.buttons.forEach(button => {
            button.addEventListener('click', () => this.setView(button.dataset.viewToggle));
        });

        // 保存されたビューまたはデフォルト（グリッド）を適用
        const savedView = localStorage.getItem(this.storageKey) || 'grid';
        this.setView(savedView, false); // アニメーションなしで初期表示
    }

    /**
     * ビューを切り替える
     * @param {string} viewName - 切り替え先のビュー名
     * @param {boolean} withAnimation - アニメーションを適用するか
     */
    setView(viewName, withAnimation = true) {
        if (!this.panels[viewName]) return;

        // パネルの表示/非表示を切り替え
        Object.values(this.panels).forEach(panel => panel.classList.add('hidden'));
        const activePanel = this.panels[viewName];
        activePanel.classList.remove('hidden');

        // ボタンのスタイルを更新
        this.buttons.forEach(button => {
            const isButtonActive = button.dataset.viewToggle === viewName;

            // アクティブ状態のスタイルを適用
            this.activeClass.forEach(cls => {
                button.classList.toggle(cls, isButtonActive);
            });

            // 非アクティブ状態のスタイルを適用
            this.inactiveClass.forEach(cls => {
                button.classList.toggle(cls, !isButtonActive);
            });
        });

        // アニメーションを適用
        if (withAnimation) {
            this.applyTransitionAnimation(activePanel);
        }

        // ローカルストレージに保存
        localStorage.setItem(this.storageKey, viewName);
    }

    /**
     * パネル切り替え時のアニメーションを適用
     * @param {HTMLElement} panel - アニメーションを適用するパネル
     */
    applyTransitionAnimation(panel) {
        panel.style.opacity = '0';
        panel.style.transform = 'translateY(10px)';

        setTimeout(() => {
            panel.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            panel.style.opacity = '1';
            panel.style.transform = 'translateY(0)';
        }, 10);
    }
}
