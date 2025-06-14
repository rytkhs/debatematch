/**
 * DOM操作ユーティリティテスト
 */

import DOMUtils from '../../resources/js/utils/dom-utils.js';
import { createElement, simulateEvent, captureConsole } from './helpers/test-utils.js';

describe('DOM操作ユーティリティ', () => {
    let testContainer;

    beforeEach(() => {
        // テスト用コンテナ作成
        testContainer = createElement('div', { id: 'test-container' });
        document.body.appendChild(testContainer);
    });

    afterEach(() => {
        // クリーンアップ
        if (testContainer.parentNode) {
            testContainer.parentNode.removeChild(testContainer);
        }
    });

    describe('安全な要素取得', () => {
        test('IDによる要素取得が正常に動作すること', () => {
            const testElement = createElement('div', { id: 'test-element' });
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeGetElement('test-element');
            expect(result).toBe(testElement);
        });

        test('存在しない要素でエラーが発生すること', () => {
            expect(() => {
                DOMUtils.safeGetElement('non-existent');
            }).toThrow('Required DOM element not found: non-existent');
        });

        test('非必須要素でnullが返されること', () => {
            const result = DOMUtils.safeGetElement('non-existent', false);
            expect(result).toBeNull();
        });

        test('無効なIDでエラーが発生すること', () => {
            expect(() => {
                DOMUtils.safeGetElement('');
            }).toThrow('Invalid element ID:');
        });
    });

    describe('クエリセレクタ', () => {
        test('基本的なセレクタが動作すること', () => {
            const testElement = createElement('div', { class: 'test-class' });
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeQuerySelector('.test-class');
            expect(result).toBe(testElement);
        });

        test('複数要素の取得が動作すること', () => {
            const element1 = createElement('div', { class: 'multi-test' });
            const element2 = createElement('div', { class: 'multi-test' });
            testContainer.appendChild(element1);
            testContainer.appendChild(element2);

            const results = DOMUtils.safeQuerySelectorAll('.multi-test');
            expect(results.length).toBe(2);
        });

        test('無効なセレクタでnullが返されること', () => {
            const result = DOMUtils.safeQuerySelector('', false);
            expect(result).toBeNull();
        });
    });

    describe('イベントリスナー管理', () => {
        test('イベントリスナーの追加が正常に動作すること', () => {
            const testElement = createElement('button');
            testContainer.appendChild(testElement);
            const handler = jest.fn();

            const result = DOMUtils.safeAddEventListener(testElement, 'click', handler);
            expect(result).toBe(true);

            // イベント発火テスト
            simulateEvent(testElement, 'click');
            expect(handler).toHaveBeenCalledTimes(1);
        });

        test('無効な要素でfalseが返されること', () => {
            const handler = jest.fn();
            const result = DOMUtils.safeAddEventListener(null, 'click', handler);
            expect(result).toBe(false);
        });

        test('無効なハンドラーでfalseが返されること', () => {
            const testElement = createElement('button');
            const result = DOMUtils.safeAddEventListener(testElement, 'click', 'not-a-function');
            expect(result).toBe(false);
        });

        test('イベントリスナーの削除が正常に動作すること', () => {
            const testElement = createElement('button');
            testContainer.appendChild(testElement);
            const handler = jest.fn();

            DOMUtils.safeAddEventListener(testElement, 'click', handler);
            const result = DOMUtils.safeRemoveEventListener(testElement, 'click', handler);
            expect(result).toBe(true);

            // イベントが発火されないことを確認
            simulateEvent(testElement, 'click');
            expect(handler).not.toHaveBeenCalled();
        });
    });

    describe('クラス操作', () => {
        test('クラスの追加が正常に動作すること', () => {
            const testElement = createElement('div');
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeClassOperation(testElement, 'add', 'test-class');
            expect(result).toBe(true);
            expect(testElement.classList.contains('test-class')).toBe(true);
        });

        test('クラスの削除が正常に動作すること', () => {
            const testElement = createElement('div', { class: 'test-class' });
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeClassOperation(testElement, 'remove', 'test-class');
            expect(result).toBe(true);
            expect(testElement.classList.contains('test-class')).toBe(false);
        });

        test('クラスの存在確認が正常に動作すること', () => {
            const testElement = createElement('div', { class: 'test-class' });
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeClassOperation(testElement, 'contains', 'test-class');
            expect(result).toBe(true);

            const nonExistResult = DOMUtils.safeClassOperation(
                testElement,
                'contains',
                'non-exist'
            );
            expect(nonExistResult).toBe(false);
        });

        test('複数クラスの操作が正常に動作すること', () => {
            const testElement = createElement('div');
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeClassOperation(testElement, 'add', ['class1', 'class2']);
            expect(result).toBe(true);
            expect(testElement.classList.contains('class1')).toBe(true);
            expect(testElement.classList.contains('class2')).toBe(true);
        });

        test('無効な要素でfalseが返されること', () => {
            const result = DOMUtils.safeClassOperation(null, 'add', 'test-class');
            expect(result).toBe(false);
        });
    });

    describe('属性操作', () => {
        test('属性の設定が正常に動作すること', () => {
            const testElement = createElement('div');
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeAttributeOperation(
                testElement,
                'set',
                'data-test',
                'value'
            );
            expect(result).toBe(true);
            expect(testElement.getAttribute('data-test')).toBe('value');
        });

        test('属性の取得が正常に動作すること', () => {
            const testElement = createElement('div', { 'data-test': 'value' });
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeAttributeOperation(testElement, 'get', 'data-test');
            expect(result).toBe('value');
        });

        test('属性の削除が正常に動作すること', () => {
            const testElement = createElement('div', { 'data-test': 'value' });
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeAttributeOperation(testElement, 'remove', 'data-test');
            expect(result).toBe(true);
            expect(testElement.hasAttribute('data-test')).toBe(false);
        });
    });

    describe('可視性判定', () => {
        test('表示されている要素でtrueが返されること', () => {
            const testElement = createElement('div');
            testContainer.appendChild(testElement);

            // テスト環境でも動作するようにoffsetWidthをモック
            Object.defineProperty(testElement, 'offsetWidth', {
                configurable: true,
                value: 100,
            });
            Object.defineProperty(testElement, 'offsetHeight', {
                configurable: true,
                value: 100,
            });

            const result = DOMUtils.isVisible(testElement);
            expect(result).toBe(true);
        });

        test('非表示要素でfalseが返されること', () => {
            const testElement = createElement('div', { class: 'hidden' });
            testContainer.appendChild(testElement);

            const result = DOMUtils.isVisible(testElement);
            expect(result).toBe(false);
        });

        test('無効な要素でfalseが返されること', () => {
            const result = DOMUtils.isVisible(null);
            expect(result).toBe(false);
        });
    });

    describe('スタイル操作', () => {
        test('単一スタイルプロパティの設定が正常に動作すること', () => {
            const testElement = createElement('div');
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeStyleOperation(testElement, 'color', 'red');
            expect(result).toBe(true);
            expect(testElement.style.color).toBe('red');
        });

        test('オブジェクト形式での一括スタイル設定が正常に動作すること', () => {
            const testElement = createElement('div');
            testContainer.appendChild(testElement);

            const styles = {
                color: 'blue',
                fontSize: '16px',
                margin: '10px',
            };

            const result = DOMUtils.safeStyleOperation(testElement, styles);
            expect(result).toBe(true);
            expect(testElement.style.color).toBe('blue');
            expect(testElement.style.fontSize).toBe('16px');
            expect(testElement.style.margin).toBe('10px');
        });

        test('無効な要素でfalseが返されること', () => {
            const result = DOMUtils.safeStyleOperation(null, 'color', 'red');
            expect(result).toBe(false);
        });

        test('無効なプロパティでfalseが返されること', () => {
            const testElement = createElement('div');
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeStyleOperation(testElement, 123, 'red');
            expect(result).toBe(false);
        });
    });

    describe('安全実行機能', () => {
        test('正常な関数が実行されること', () => {
            const operation = jest.fn(() => 'success');
            const result = DOMUtils.safeExecute(operation, 'test');

            expect(operation).toHaveBeenCalledTimes(1);
            expect(result).toBe('success');
        });

        test('エラーが発生した場合にフォールバック値が返されること', () => {
            const operation = jest.fn(() => {
                throw new Error('Test error');
            });

            const result = DOMUtils.safeExecute(operation, 'test', 'fallback');
            expect(result).toBe('fallback');
        });

        test('無効な関数でフォールバック値が返されること', () => {
            const result = DOMUtils.safeExecute('not-a-function', 'test', 'fallback');
            expect(result).toBe('fallback');
        });
    });

    describe('トグル機能', () => {
        test('クラスのトグルが正常に動作すること', () => {
            const testElement = createElement('div');
            testContainer.appendChild(testElement);

            // クラス追加
            let result = DOMUtils.safeClassOperation(testElement, 'toggle', 'test-class');
            expect(result).toBe(true);
            expect(testElement.classList.contains('test-class')).toBe(true);

            // クラス削除
            result = DOMUtils.safeClassOperation(testElement, 'toggle', 'test-class');
            expect(result).toBe(false);
            expect(testElement.classList.contains('test-class')).toBe(false);
        });

        test('無効なクラス操作でfalseが返されること', () => {
            const testElement = createElement('div');
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeClassOperation(testElement, 'invalid-action', 'test-class');
            expect(result).toBe(false);
        });

        test('空のクラス名でfalseが返されること', () => {
            const testElement = createElement('div');
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeClassOperation(testElement, 'add', '');
            expect(result).toBe(false);
        });
    });

    describe('属性has操作', () => {
        test('属性の存在確認が正常に動作すること', () => {
            const testElement = createElement('div', { 'data-test': 'value' });
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeAttributeOperation(testElement, 'has', 'data-test');
            expect(result).toBe(true);

            const nonExistResult = DOMUtils.safeAttributeOperation(
                testElement,
                'has',
                'data-nonexist'
            );
            expect(nonExistResult).toBe(false);
        });

        test('無効な属性操作でnullが返されること', () => {
            const testElement = createElement('div');
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeAttributeOperation(
                testElement,
                'invalid-action',
                'data-test'
            );
            expect(result).toBeNull();
        });

        test('無効な属性名でnullが返されること', () => {
            const testElement = createElement('div');
            testContainer.appendChild(testElement);

            const result = DOMUtils.safeAttributeOperation(testElement, 'get', '');
            expect(result).toBeNull();
        });
    });

    describe('エラーハンドリング統合テスト', () => {
        test('存在しない要素への複数操作が適切に処理されること', () => {
            // 複数の操作を連続で実行
            const element = DOMUtils.safeGetElement('non-existent', false);
            const addResult = DOMUtils.safeAddEventListener(element, 'click', jest.fn());
            const classResult = DOMUtils.safeClassOperation(element, 'add', 'test');

            expect(element).toBeNull();
            expect(addResult).toBe(false);
            expect(classResult).toBe(false);
        });
    });
});
