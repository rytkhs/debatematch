import '@testing-library/jest-dom';

// 統一テスト環境のインポート
import { setupUnifiedTestEnvironment } from './utils/test-helpers.js';

// 統一テスト環境のセットアップ
setupUnifiedTestEnvironment();

// DOMのモック
global.document.createRange = () => ({
    setStart: jest.fn(),
    setEnd: jest.fn(),
    commonAncestorContainer: {
        nodeName: 'BODY',
        ownerDocument: document,
    },
});

// Material Iconsのモック
Object.defineProperty(window, 'getComputedStyle', {
    value: () => ({
        getPropertyValue: prop => {
            return '';
        },
    }),
});

// console.error/warnの適切な処理
const originalError = console.error;
const originalWarn = console.warn;

global.console = {
    ...console,
    error: jest.fn((message, ...args) => {
        // テスト環境では基本的にコンソールエラーを抑制
        const suppressPatterns = [
            'Not implemented: navigation',
            'Not implemented: HTMLFormElement.prototype.requestSubmit',
            '[DOMUtils]', // DOMUtilsの意図的エラー
            '[test]', // テスト用エラー
        ];

        const shouldSuppress = suppressPatterns.some(
            pattern => message && message.toString().includes(pattern)
        );

        if (shouldSuppress) {
            return; // テスト時のエラーは抑制
        }

        // 予期しない重要なエラーのみ表示
        originalError.apply(console, ['[UNEXPECTED ERROR]', message, ...args]);
    }),
    warn: jest.fn((message, ...args) => {
        // テスト環境では警告も基本的に抑制
        const suppressPatterns = [
            '[DOMUtils]', // DOMUtilsの意図的警告
        ];

        const shouldSuppress = suppressPatterns.some(
            pattern => message && message.toString().includes(pattern)
        );

        if (shouldSuppress) {
            return; // テスト時の警告は抑制
        }

        // 予期しない警告のみ表示
        originalWarn.apply(console, ['[UNEXPECTED WARN]', message, ...args]);
    }),
};

// グローバル関数のモック
global.scrollTo = jest.fn();
global.scrollBy = jest.fn();

// Navigation APIのモック（jsdom対応）
Object.defineProperty(window, 'location', {
    value: {
        href: 'http://localhost/',
        origin: 'http://localhost',
        pathname: '/',
        search: '',
        hash: '',
        assign: jest.fn(),
        replace: jest.fn(),
        reload: jest.fn(),
    },
    writable: true,
});

// timeoutのモック
jest.useFakeTimers();

// テスト後のクリーンアップ
afterEach(() => {
    // タイマーをクリア
    jest.clearAllTimers();

    // フェイクタイマーをリセット
    jest.useRealTimers();
    jest.useFakeTimers();
});

// グローバルエラーハンドラー
process.on('unhandledRejection', (reason, promise) => {
    // 未処理のPromise rejectionは重要なエラーなので必ず表示
    originalError.apply(console, ['[UNHANDLED REJECTION]', 'Promise:', promise, 'Reason:', reason]);
});

// テストファイル終了時の最終クリーンアップ
afterAll(() => {
    jest.useRealTimers();
    console.error = originalError;
    console.warn = originalWarn;
});
