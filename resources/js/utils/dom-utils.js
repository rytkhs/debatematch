/**
 * DOM操作の安全性を確保する共通ユーティリティ
 * @class DOMUtils
 * @example
 * import DOMUtils from '@/utils/dom-utils';
 *
 * const element = DOMUtils.safeGetElement('my-id');
 * DOMUtils.safeAddEventListener(element, 'click', handler);
 */
export default class DOMUtils {
    /**
     * 安全なDOM要素取得（ID指定）
     * @static
     * @param {string} id - 要素ID
     * @param {boolean} [required=true] - 必須かどうか
     * @param {string} [context='DOMUtils'] - エラーログのコンテキスト
     * @returns {Element|null} DOM要素
     * @throws {Error} 必須要素が見つからない場合
     */
    static safeGetElement(id, required = true, context = 'DOMUtils') {
        if (!id || typeof id !== 'string') {
            const error = new Error(`Invalid element ID: ${id}`);
            console.error(`[${context}]`, error.message);
            if (required) throw error;
            return null;
        }

        const element = document.getElementById(id);
        if (!element && required) {
            const error = new Error(`Required DOM element not found: ${id}`);
            console.error(`[${context}]`, error.message);
            throw error;
        }

        return element;
    }

    /**
     * 安全なクエリセレクタ
     * @static
     * @param {string} selector - セレクタ
     * @param {boolean} [required=true] - 必須かどうか
     * @param {string} [context='DOMUtils'] - エラーログのコンテキスト
     * @returns {Element|null} DOM要素
     * @throws {Error} 必須要素が見つからない場合
     */
    static safeQuerySelector(selector, required = true, context = 'DOMUtils') {
        if (!selector || typeof selector !== 'string') {
            const error = new Error(`Invalid selector: ${selector}`);
            console.error(`[${context}]`, error.message);
            if (required) throw error;
            return null;
        }

        try {
            const element = document.querySelector(selector);
            if (!element && required) {
                const error = new Error(`Required DOM element not found: ${selector}`);
                console.error(`[${context}]`, error.message);
                throw error;
            }
            return element;
        } catch (error) {
            console.error(`[${context}] Invalid selector syntax: ${selector}`, error);
            if (required) throw error;
            return null;
        }
    }

    /**
     * 安全な複数要素取得
     * @static
     * @param {string} selector - セレクタ
     * @param {boolean} [required=true] - 必須かどうか（最低1つ必要）
     * @param {string} [context='DOMUtils'] - エラーログのコンテキスト
     * @returns {NodeList|Array} DOM要素のリスト
     */
    static safeQuerySelectorAll(selector, required = true, context = 'DOMUtils') {
        if (!selector || typeof selector !== 'string') {
            const error = new Error(`Invalid selector: ${selector}`);
            console.error(`[${context}]`, error.message);
            if (required) throw error;
            return [];
        }

        try {
            const elements = document.querySelectorAll(selector);
            if (elements.length === 0 && required) {
                const error = new Error(`No elements found for selector: ${selector}`);
                console.error(`[${context}]`, error.message);
                throw error;
            }
            return elements;
        } catch (error) {
            console.error(`[${context}] Invalid selector syntax: ${selector}`, error);
            if (required) throw error;
            return [];
        }
    }

    /**
     * 安全なイベントリスナー追加
     * @static
     * @param {Element|null} element - 対象要素
     * @param {string} eventType - イベントタイプ
     * @param {Function} handler - イベントハンドラー
     * @param {Object|boolean} [options=false] - イベントオプション
     * @param {string} [context='DOMUtils'] - エラーログのコンテキスト
     * @returns {boolean} 成功したかどうか
     */
    static safeAddEventListener(
        element,
        eventType,
        handler,
        options = false,
        context = 'DOMUtils'
    ) {
        if (!element) {
            console.warn(`[${context}] Cannot add event listener: element is null`);
            return false;
        }

        if (!eventType || typeof eventType !== 'string') {
            console.error(`[${context}] Invalid event type: ${eventType}`);
            return false;
        }

        if (!handler || typeof handler !== 'function') {
            console.error(`[${context}] Invalid event handler: ${handler}`);
            return false;
        }

        try {
            element.addEventListener(eventType, handler, options);
            return true;
        } catch (error) {
            console.error(`[${context}] Failed to add event listener:`, error);
            return false;
        }
    }

    /**
     * 安全なイベントリスナー削除
     * @static
     * @param {Element|null} element - 対象要素
     * @param {string} eventType - イベントタイプ
     * @param {Function} handler - イベントハンドラー
     * @param {Object|boolean} [options=false] - イベントオプション
     * @param {string} [context='DOMUtils'] - エラーログのコンテキスト
     * @returns {boolean} 成功したかどうか
     */
    static safeRemoveEventListener(
        element,
        eventType,
        handler,
        options = false,
        context = 'DOMUtils'
    ) {
        if (!element) {
            console.warn(`[${context}] Cannot remove event listener: element is null`);
            return false;
        }

        try {
            element.removeEventListener(eventType, handler, options);
            return true;
        } catch (error) {
            console.error(`[${context}] Failed to remove event listener:`, error);
            return false;
        }
    }

    /**
     * 安全なクラス操作
     * @static
     * @param {Element|null} element - 対象要素
     * @param {string} action - 操作タイプ ('add', 'remove', 'toggle', 'contains')
     * @param {string|Array<string>} classNames - クラス名（複数可）
     * @param {string} [context='DOMUtils'] - エラーログのコンテキスト
     * @returns {boolean|null} 操作結果（containsの場合は真偽値、その他は成功/失敗）
     */
    static safeClassOperation(element, action, classNames, context = 'DOMUtils') {
        if (!element) {
            console.warn(`[${context}] Cannot perform class operation: element is null`);
            return false;
        }

        const classes = Array.isArray(classNames) ? classNames : [classNames];
        const validClasses = classes.filter(cls => cls && typeof cls === 'string' && cls.trim());

        if (validClasses.length === 0) {
            console.warn(`[${context}] No valid class names provided`);
            return false;
        }

        try {
            switch (action) {
                case 'add':
                    element.classList.add(...validClasses);
                    return true;
                case 'remove':
                    element.classList.remove(...validClasses);
                    return true;
                case 'toggle':
                    // toggleは1つのクラスのみサポート
                    return element.classList.toggle(validClasses[0]);
                case 'contains':
                    // containsは1つのクラスのみサポート
                    return element.classList.contains(validClasses[0]);
                default:
                    console.error(`[${context}] Invalid class operation: ${action}`);
                    return false;
            }
        } catch (error) {
            console.error(`[${context}] Class operation failed:`, error);
            return false;
        }
    }

    /**
     * 安全な属性操作
     * @static
     * @param {Element|null} element - 対象要素
     * @param {string} action - 操作タイプ ('get', 'set', 'remove', 'has')
     * @param {string} attributeName - 属性名
     * @param {string} [value] - 設定値（setの場合）
     * @param {string} [context='DOMUtils'] - エラーログのコンテキスト
     * @returns {string|boolean|null} 操作結果
     */
    static safeAttributeOperation(element, action, attributeName, value, context = 'DOMUtils') {
        if (!element) {
            console.warn(`[${context}] Cannot perform attribute operation: element is null`);
            return null;
        }

        if (!attributeName || typeof attributeName !== 'string') {
            console.error(`[${context}] Invalid attribute name: ${attributeName}`);
            return null;
        }

        try {
            switch (action) {
                case 'get':
                    return element.getAttribute(attributeName);
                case 'set':
                    element.setAttribute(attributeName, value || '');
                    return true;
                case 'remove':
                    element.removeAttribute(attributeName);
                    return true;
                case 'has':
                    return element.hasAttribute(attributeName);
                default:
                    console.error(`[${context}] Invalid attribute operation: ${action}`);
                    return null;
            }
        } catch (error) {
            console.error(`[${context}] Attribute operation failed:`, error);
            return null;
        }
    }

    /**
     * 安全なスタイル操作
     * @static
     * @param {Element|null} element - 対象要素
     * @param {string|Object} property - スタイルプロパティ名またはスタイルオブジェクト
     * @param {string} [value] - 設定値
     * @param {string} [context='DOMUtils'] - エラーログのコンテキスト
     * @returns {boolean} 成功したかどうか
     */
    static safeStyleOperation(element, property, value, context = 'DOMUtils') {
        if (!element) {
            console.warn(`[${context}] Cannot perform style operation: element is null`);
            return false;
        }

        try {
            if (typeof property === 'object' && property !== null) {
                // オブジェクト形式での一括設定
                Object.keys(property).forEach(key => {
                    if (property[key] !== undefined) {
                        element.style[key] = property[key];
                    }
                });
                return true;
            } else if (typeof property === 'string') {
                // 単一プロパティの設定
                element.style[property] = value || '';
                return true;
            } else {
                console.error(`[${context}] Invalid style property: ${property}`);
                return false;
            }
        } catch (error) {
            console.error(`[${context}] Style operation failed:`, error);
            return false;
        }
    }

    /**
     * 要素の可視性チェック
     * @static
     * @param {Element|null} element - 対象要素
     * @param {string} [context='DOMUtils'] - エラーログのコンテキスト
     * @returns {boolean} 可視かどうか
     */
    static isVisible(element, context = 'DOMUtils') {
        if (!element) {
            console.warn(`[${context}] Cannot check visibility: element is null`);
            return false;
        }

        try {
            return (
                !!(
                    element.offsetWidth ||
                    element.offsetHeight ||
                    element.getClientRects().length
                ) && !element.classList.contains('hidden')
            );
        } catch (error) {
            console.error(`[${context}] Visibility check failed:`, error);
            return false;
        }
    }

    /**
     * 安全なDOM操作の実行（try-catch付き）
     * @static
     * @param {Function} operation - 実行する操作
     * @param {string} [context='DOMUtils'] - エラーログのコンテキスト
     * @param {*} [fallbackValue=null] - エラー時の戻り値
     * @returns {*} 操作結果またはfallbackValue
     */
    static safeExecute(operation, context = 'DOMUtils', fallbackValue = null) {
        if (typeof operation !== 'function') {
            console.error(`[${context}] Invalid operation: not a function`);
            return fallbackValue;
        }

        try {
            return operation();
        } catch (error) {
            console.error(`[${context}] Operation failed:`, error);
            return fallbackValue;
        }
    }
}
