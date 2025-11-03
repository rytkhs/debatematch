/**
 * Records Show Page JavaScript
 * ディベート記録詳細ページのタブ切り替え機能とコピー機能
 */

import { DebateReportCopier } from '../components/debate-report-copier.js';

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
 * コピーボタンの成功フィードバックを表示
 * @param {HTMLElement} button - コピーボタン要素
 */
function showSuccessFeedback(button) {
    const icon = button.querySelector('i');
    const text = button.querySelector('.button-text');

    icon.className = 'fas fa-check mr-1 sm:mr-2';
    text.textContent = button.dataset.copiedText;
    button.classList.add('bg-green-100', 'text-green-700');
    button.classList.remove('bg-gray-100', 'text-gray-700');
}

/**
 * コピーボタンのエラーフィードバックを表示
 * @param {HTMLElement} button - コピーボタン要素
 */
function showErrorFeedback(button) {
    const icon = button.querySelector('i');
    const text = button.querySelector('.button-text');

    icon.className = 'fas fa-times mr-1 sm:mr-2';
    text.textContent = button.dataset.errorText;
    button.classList.add('bg-red-100', 'text-red-700');
    button.classList.remove('bg-gray-100', 'text-gray-700');
}

/**
 * コピーボタンを元の状態にリセット
 * @param {HTMLElement} button - コピーボタン要素
 */
function resetButton(button) {
    const icon = button.querySelector('i');
    const text = button.querySelector('.button-text');

    icon.className = 'fas fa-copy mr-1 sm:mr-2';
    text.textContent = button.dataset.originalText;
    button.classList.remove('bg-green-100', 'text-green-700', 'bg-red-100', 'text-red-700');
    button.classList.add('bg-gray-100', 'text-gray-700');
    button.disabled = false;
}

/**
 * コピーボタンの初期化
 */
function initializeCopyButton() {
    const copyButton = document.getElementById('copy-debate-report-btn');

    if (!copyButton) {
        return;
    }

    // Bladeから渡されたデータを取得
    const debateData = window.debateData;
    const locale = document.documentElement.lang || 'ja';
    const translations = window.debateReportTranslations;

    if (!debateData) {
        console.error('debateData is not available');
        return;
    }

    if (!translations) {
        console.error('debateReportTranslations is not available');
        return;
    }

    // DebateReportCopierのインスタンスを作成
    const copier = new DebateReportCopier(debateData, locale, translations);

    // コピーボタンのクリックイベント
    copyButton.addEventListener('click', async () => {
        // ボタンを無効化（連続クリック防止）
        copyButton.disabled = true;

        // コピー実行
        const success = await copier.copyToClipboard();

        // フィードバック表示
        if (success) {
            showSuccessFeedback(copyButton);

            // GA4イベント送信
            if (typeof window.gtag !== 'undefined') {
                // eslint-disable-next-line no-undef
                gtag('event', 'copy_markdown_report', {
                    event_category: 'debate_record',
                    event_label: 'markdown_copy',
                    page_location: window.location.pathname,
                    debate_id: debateData?.id || null,
                });
            }
        } else {
            showErrorFeedback(copyButton);
        }

        // 2秒後にボタンを元に戻す
        setTimeout(() => {
            resetButton(copyButton);
        }, 2000);
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

    // コピーボタンの初期化
    initializeCopyButton();
}

// DOMContentLoaded時の初期化
document.addEventListener('DOMContentLoaded', initializeRecordsShow);

// グローバル関数として公開（Bladeテンプレートから呼び出すため）
window.showTab = showTab;
