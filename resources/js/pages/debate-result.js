/**
 * Debate Result Page JavaScript
 * ディベート結果ページのコピー機能
 */

import { DebateReportCopier } from '../components/debate-report-copier.js';

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
 * ページ初期化
 */
function initializeDebateResult() {
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
        } else {
            showErrorFeedback(copyButton);
        }

        // 2秒後にボタンを元に戻す
        setTimeout(() => {
            resetButton(copyButton);
        }, 2000);
    });
}

// DOMContentLoaded時の初期化
document.addEventListener('DOMContentLoaded', initializeDebateResult);
