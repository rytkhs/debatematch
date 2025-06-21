/**
 * Records Show Page JavaScript
 * ディベート記録詳細ページのタブ切り替え機能
 */

/**
 * タブを表示する関数
 * @param {string} tabId - 表示するタブのID ('result' または 'debate')
 */
function showTab(tabId) {
    // タブの表示/非表示を切り替え
    document.getElementById('content-result').classList.toggle('hidden', tabId !== 'result');
    document.getElementById('content-debate').classList.toggle('hidden', tabId !== 'debate');

    // タブのスタイルを更新
    const tabs = {
        result: document.getElementById('tab-result'),
        debate: document.getElementById('tab-debate'),
    };

    // 各タブのアクティブ状態を更新
    Object.entries(tabs).forEach(([id, tab]) => {
        const isActive = id === tabId;
        tab.setAttribute('data-active', isActive ? 'true' : 'false');
        updateTabStyle(tab);
    });
}

/**
 * タブスタイルを更新する関数
 * @param {HTMLElement} tab - スタイルを更新するタブ要素
 */
function updateTabStyle(tab) {
    const isActive = tab.getAttribute('data-active') === 'true';

    if (isActive) {
        tab.classList.add('text-primary', 'border-primary');
        tab.classList.remove('text-gray-500', 'border-transparent');
    } else {
        tab.classList.add('text-gray-500', 'border-transparent');
        tab.classList.remove('text-primary', 'border-primary');
    }
}

/**
 * タブにホバーエフェクトを追加する関数
 * @param {HTMLElement} tab - ホバーエフェクトを追加するタブ要素
 */
function addTabHoverEffects(tab) {
    tab.addEventListener('mouseenter', function () {
        if (this.getAttribute('data-active') !== 'true') {
            this.classList.add('text-gray-700', 'border-gray-300');
            this.classList.remove('text-gray-500');
        }
    });

    tab.addEventListener('mouseleave', function () {
        if (this.getAttribute('data-active') !== 'true') {
            this.classList.remove('text-gray-700', 'border-gray-300');
            this.classList.add('text-gray-500');
        }
    });
}

/**
 * ページ初期化
 */
function initializeRecordsShow() {
    // タブスタイルの初期化
    document.querySelectorAll('.tab-button').forEach(tab => {
        updateTabStyle(tab);
        addTabHoverEffects(tab);
    });
}

// DOMContentLoaded時の初期化
document.addEventListener('DOMContentLoaded', initializeRecordsShow);

// グローバル関数として公開（Bladeテンプレートから呼び出すため）
window.showTab = showTab;
