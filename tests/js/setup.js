require('@testing-library/jest-dom');

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
        getPropertyValue: (prop) => {
            return '';
        }
    })
});

// console.error/warnをモック（テスト中の不要な出力を抑制）
global.console = {
    ...console,
    error: jest.fn(),
    warn: jest.fn(),
};

// グローバル関数のモック
global.scrollTo = jest.fn();

// timeoutのモック
jest.useFakeTimers();
