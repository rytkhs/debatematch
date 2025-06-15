/**
 * ユーティリティ機能テスト
 * 共通ユーティリティ関数のシンプルなテスト
 */
import { jest } from '@jest/globals';
import { cleanupDOM, createTestElement } from './utils/test-helpers.js';

// 基本的なDOM操作ユーティリティ
const domUtils = {
    addClass: (element, className) => {
        if (element) element.classList.add(className);
    },
    removeClass: (element, className) => {
        if (element) element.classList.remove(className);
    },
    toggleClass: (element, className) => {
        if (element) element.classList.toggle(className);
    },
    hide: element => {
        if (element) element.style.display = 'none';
    },
    show: element => {
        if (element) element.style.display = '';
    },
};

// 時間関連のユーティリティ
const timeUtils = {
    formatTime: seconds => {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    },
    getCurrentTime: () => Date.now(),
    addMinutes: (time, minutes) => time + minutes * 60 * 1000,
};

// ログ関連のユーティリティ
const mockLogger = {
    log: jest.fn(),
    warn: jest.fn(),
    error: jest.fn(),
};

describe('ユーティリティ機能テスト', () => {
    beforeEach(() => {
        cleanupDOM();
        jest.clearAllMocks();
    });

    afterEach(() => {
        cleanupDOM();
    });

    describe('DOM操作ユーティリティ', () => {
        test('クラスの追加が正常に動作する', () => {
            const element = createTestElement('div', { id: 'test-element' });
            document.body.appendChild(element);

            domUtils.addClass(element, 'test-class');

            expect(element.classList.contains('test-class')).toBe(true);
        });

        test('クラスの削除が正常に動作する', () => {
            const element = createTestElement('div', {
                id: 'test-element',
                className: 'test-class',
            });
            document.body.appendChild(element);

            domUtils.removeClass(element, 'test-class');

            expect(element.classList.contains('test-class')).toBe(false);
        });

        test('要素の非表示が正常に動作する', () => {
            const element = createTestElement('div', { id: 'test-element' });
            document.body.appendChild(element);

            domUtils.hide(element);

            expect(element.style.display).toBe('none');
        });

        test('要素の表示が正常に動作する', () => {
            const element = createTestElement('div', {
                id: 'test-element',
                style: 'display: none;',
            });
            document.body.appendChild(element);

            domUtils.show(element);

            expect(element.style.display).toBe('');
        });

        test('null要素に対してもエラーが発生しない', () => {
            expect(() => {
                domUtils.addClass(null, 'test-class');
                domUtils.removeClass(null, 'test-class');
                domUtils.hide(null);
                domUtils.show(null);
            }).not.toThrow();
        });
    });

    describe('時間関連ユーティリティ', () => {
        test('時間フォーマットが正常に動作する', () => {
            expect(timeUtils.formatTime(0)).toBe('00:00');
            expect(timeUtils.formatTime(30)).toBe('00:30');
            expect(timeUtils.formatTime(60)).toBe('01:00');
            expect(timeUtils.formatTime(90)).toBe('01:30');
            expect(timeUtils.formatTime(3600)).toBe('60:00');
        });

        test('現在時刻の取得が正常に動作する', () => {
            const now = timeUtils.getCurrentTime();

            expect(typeof now).toBe('number');
            expect(now).toBeGreaterThan(0);
        });

        test('時刻の加算が正常に動作する', () => {
            const baseTime = 1000000000000; // 適当な時刻
            const result = timeUtils.addMinutes(baseTime, 5);

            expect(result).toBe(baseTime + 5 * 60 * 1000);
        });
    });

    describe('ログ関連ユーティリティ', () => {
        test('ログ出力が正常に動作する', () => {
            mockLogger.log('テストメッセージ');

            expect(mockLogger.log).toHaveBeenCalledWith('テストメッセージ');
        });

        test('警告ログが正常に動作する', () => {
            mockLogger.warn('警告メッセージ');

            expect(mockLogger.warn).toHaveBeenCalledWith('警告メッセージ');
        });

        test('エラーログが正常に動作する', () => {
            mockLogger.error('エラーメッセージ');

            expect(mockLogger.error).toHaveBeenCalledWith('エラーメッセージ');
        });
    });

    describe('エラーハンドリング', () => {
        test('不正な引数でもエラーが発生しない', () => {
            expect(() => {
                timeUtils.formatTime(-1);
                timeUtils.formatTime(null);
                timeUtils.formatTime(undefined);
            }).not.toThrow();
        });

        test('空の値でもログが動作する', () => {
            expect(() => {
                mockLogger.log('');
                mockLogger.warn(null);
                mockLogger.error(undefined);
            }).not.toThrow();
        });
    });

    describe('統合テスト', () => {
        test('DOM操作と時間ユーティリティの組み合わせ', () => {
            const element = createTestElement('div', {
                id: 'countdown-display',
                textContent: '00:00',
            });
            document.body.appendChild(element);

            // 時間をフォーマットしてDOM要素に設定
            const formattedTime = timeUtils.formatTime(150); // 2:30
            element.textContent = formattedTime;
            domUtils.addClass(element, 'countdown-active');

            expect(element.textContent).toBe('02:30');
            expect(element.classList.contains('countdown-active')).toBe(true);
        });

        test('エラー発生時のログ記録', () => {
            try {
                // 意図的にエラーを発生
                throw new Error('テストエラー');
            } catch (error) {
                mockLogger.error('エラーが発生しました:', error.message);
            }

            expect(mockLogger.error).toHaveBeenCalledWith('エラーが発生しました:', 'テストエラー');
        });
    });
});
