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
        // jsdom固有の無害なエラーのみ抑制
        const jsdomOnlyPatterns = [
            'Not implemented: navigation',
            'Not implemented: HTMLFormElement.prototype.requestSubmit',
        ];

        const shouldSuppress = jsdomOnlyPatterns.some(
            pattern => message && message.toString().includes(pattern)
        );

        if (shouldSuppress) {
            return; // jsdom固有のエラーは抑制
        }

        // その他のエラーは重要なので表示
        originalError.apply(console, ['[TEST ERROR]', message, ...args]);
    }),
    warn: jest.fn((message, ...args) => {
        // 警告は基本的に表示
        originalWarn.apply(console, ['[TEST WARN]', message, ...args]);
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
