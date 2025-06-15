/**
 * Jest テスト用ヘルパー関数集
 * 共通的なテスト環境構築とモック設定
 */
// setImmediateポリフィル
if (typeof setImmediate === 'undefined') {
    global.setImmediate = (callback, ...args) => {
        return setTimeout(() => callback(...args), 0);
    };
}

if (typeof clearImmediate === 'undefined') {
    global.clearImmediate = id => {
        return clearTimeout(id);
    };
}

/**
 * 統一テストセットアップ
 */
export function setupUnifiedTestEnvironment() {
    // DOM環境のクリーンアップ
    cleanupDOM();

    // 共通モックの設定
    mockStorage();
    mockIntersectionObserver();
    mockResizeObserver();
    mockMatchMedia();

    // グローバルエラーハンドリング
    setupGlobalErrorHandling();
}

/**
 * グローバルエラーハンドリング設定
 */
function setupGlobalErrorHandling() {
    // 未処理のPromise rejectionをキャッチ（テスト環境では抑制）
    process.on('unhandledRejection', (reason, promise) => {
        // テスト環境では意図的なrejectionが多いので基本的に抑制
        // 必要に応じてログを有効化
    });

    // jsdomのnavigationエラーを抑制
    const originalError = console.error;
    console.error = (...args) => {
        const message = args[0];
        if (typeof message === 'string' && message.includes('Not implemented: navigation')) {
            return; // navigationエラーは無視
        }
        // その他のエラーも基本的に抑制（テスト環境）
    };
}

/**
 * テストヘルパー関数
 * テスト共通で使用するユーティリティ関数
 */

/**
 * DOMクリーンアップ
 */
export function cleanupDOM() {
    // body要素のクリーンアップ
    if (document.body) {
        document.body.innerHTML = '';
        // body要素を新しい要素で置換することで、すべてのイベントリスナーを削除
        const bodyClone = document.body.cloneNode(false);
        document.body.parentNode.replaceChild(bodyClone, document.body);
    }

    // head要素のクリーンアップ
    if (document.head) {
        const elementsToRemove = document.head.querySelectorAll(
            'style, link[rel="stylesheet"], script'
        );
        elementsToRemove.forEach(element => {
            if (!element.hasAttribute('data-keep')) {
                element.remove();
            }
        });
    }
}

/**
 * テスト用DOM要素作成
 */
export function createTestElement(tag = 'div', attributes = {}, innerHTML = '') {
    let element;

    try {
        element = document.createElement(tag);
    } catch (error) {
        console.warn(`Failed to create element with tag '${tag}', falling back to div`);
        element = document.createElement('div');
    }

    // 属性の安全な設定
    Object.keys(attributes).forEach(key => {
        try {
            if (key === 'className') {
                element.className = attributes[key];
            } else if (key === 'dataset') {
                Object.keys(attributes[key]).forEach(dataKey => {
                    element.dataset[dataKey] = attributes[key][dataKey];
                });
            } else if (key === 'style' && typeof attributes[key] === 'object') {
                Object.assign(element.style, attributes[key]);
            } else {
                element.setAttribute(key, attributes[key]);
            }
        } catch (error) {
            console.warn(`Failed to set attribute '${key}' on element:`, error);
        }
    });

    // HTMLコンテンツの安全な設定
    if (innerHTML) {
        try {
            element.innerHTML = innerHTML;
        } catch (error) {
            console.warn(`Failed to set innerHTML:`, error);
            element.textContent = typeof innerHTML === 'string' ? innerHTML : '';
        }
    }

    return element;
}

/**
 * テスト用DOM構造作成
 */
export function createTestDOM(html) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    document.body.appendChild(wrapper);
    return wrapper;
}

/**
 * CSS疑似クラス・メディアクエリのモック
 */
export function mockCSSSupports(supports = true) {
    global.CSS = global.CSS || {};
    global.CSS.supports = jest.fn(() => supports);
}

/**
 * window.matchMediaのモック
 */
export function mockMatchMedia(matches = false) {
    Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: jest.fn().mockImplementation(query => ({
            matches,
            media: query,
            onchange: null,
            addListener: jest.fn(),
            removeListener: jest.fn(),
            addEventListener: jest.fn(),
            removeEventListener: jest.fn(),
            dispatchEvent: jest.fn(),
        })),
    });
}

/**
 * IntersectionObserverのモック
 */
export function mockIntersectionObserver() {
    global.IntersectionObserver = jest.fn().mockImplementation((callback, options) => ({
        observe: jest.fn(element => {
            // デフォルトで要素が表示されている状態をシミュレート
            callback([
                {
                    target: element,
                    isIntersecting: true,
                    intersectionRatio: 1,
                    boundingClientRect: element.getBoundingClientRect(),
                    intersectionRect: element.getBoundingClientRect(),
                    rootBounds: null,
                    time: Date.now(),
                },
            ]);
        }),
        unobserve: jest.fn(),
        disconnect: jest.fn(),
    }));
}

/**
 * ResizeObserverのモック
 */
export function mockResizeObserver() {
    global.ResizeObserver = jest.fn().mockImplementation(callback => ({
        observe: jest.fn(element => {
            // デフォルトでサイズ変更をシミュレート
            callback([
                {
                    target: element,
                    contentRect: {
                        width: 100,
                        height: 100,
                        top: 0,
                        left: 0,
                        bottom: 100,
                        right: 100,
                    },
                },
            ]);
        }),
        unobserve: jest.fn(),
        disconnect: jest.fn(),
    }));
}

/**
 * localStorage/sessionStorageのモック
 */
export function mockStorage() {
    const createStorageMock = () => {
        const storage = {};
        return {
            getItem: jest.fn(key => storage[key] || null),
            setItem: jest.fn((key, value) => {
                storage[key] = value.toString();
            }),
            removeItem: jest.fn(key => {
                delete storage[key];
            }),
            clear: jest.fn(() => {
                Object.keys(storage).forEach(key => delete storage[key]);
            }),
            key: jest.fn(index => {
                const keys = Object.keys(storage);
                return keys[index] || null;
            }),
            get length() {
                return Object.keys(storage).length;
            },
        };
    };

    Object.defineProperty(window, 'localStorage', {
        value: createStorageMock(),
    });

    Object.defineProperty(window, 'sessionStorage', {
        value: createStorageMock(),
    });
}

/**
 * フェッチAPIのモック
 */
export function mockFetch(responseData = {}, ok = true, status = 200) {
    global.fetch = jest.fn(() =>
        Promise.resolve({
            ok,
            status,
            json: () => Promise.resolve(responseData),
            text: () => Promise.resolve(JSON.stringify(responseData)),
        })
    );
}

/**
 * CSRFトークンのモック
 */
export function mockCSRFToken(token = 'mock-csrf-token') {
    const metaTag = createTestElement('meta', {
        name: 'csrf-token',
        content: token,
    });
    document.head.appendChild(metaTag);
}

/**
 * タイマーのテストヘルパー
 */
export function advanceTimersByTime(time) {
    jest.advanceTimersByTime(time);
}

/**
 * 非同期処理の完了を待つ
 */
export function waitForAsync() {
    return new Promise(resolve => setImmediate(resolve));
}

/**
 * DOMイベントの発火
 */
export function triggerEvent(element, eventType, eventData = {}) {
    const event = new Event(eventType, { bubbles: true, cancelable: true });
    Object.keys(eventData).forEach(key => {
        event[key] = eventData[key];
    });
    element.dispatchEvent(event);
}

/**
 * キーボードイベントの発火
 */
export function triggerKeyEvent(element, eventType, keyCode, options = {}) {
    const event = new KeyboardEvent(eventType, {
        keyCode,
        which: keyCode,
        bubbles: true,
        cancelable: true,
        ...options,
    });
    element.dispatchEvent(event);
}

/**
 * マウスイベントの発火
 */
export function triggerMouseEvent(element, eventType, options = {}) {
    const event = new MouseEvent(eventType, {
        bubbles: true,
        cancelable: true,
        ...options,
    });
    element.dispatchEvent(event);
}

/**
 * ページ可視性APIのモック
 */
export function mockPageVisibility(hidden = false) {
    Object.defineProperty(document, 'hidden', {
        writable: true,
        value: hidden,
    });

    Object.defineProperty(document, 'visibilityState', {
        writable: true,
        value: hidden ? 'hidden' : 'visible',
    });
}

/**
 * ネットワーク状態のモック
 */
export function mockNetworkStatus(online = true) {
    Object.defineProperty(navigator, 'onLine', {
        writable: true,
        value: online,
    });
}

/**
 * 時間関数のモック（現在時刻固定）
 */
export function mockDateNow(timestamp = 1640995200000) {
    // 2022-01-01 00:00:00 UTC
    jest.spyOn(Date, 'now').mockReturnValue(timestamp);
}

/**
 * コンソール出力のキャプチャ
 */
export function captureConsole() {
    const originalConsole = { ...console };
    const captured = {
        log: [],
        warn: [],
        error: [],
    };

    console.log = jest.fn((...args) => captured.log.push(args));
    console.warn = jest.fn((...args) => captured.warn.push(args));
    console.error = jest.fn((...args) => captured.error.push(args));

    return {
        captured,
        restore: () => {
            Object.assign(console, originalConsole);
        },
    };
}

/**
 * DOM要素のイベントリスナーをモック化
 */
export function mockEventListener() {
    const originalAddEventListener = Element.prototype.addEventListener;
    const originalRemoveEventListener = Element.prototype.removeEventListener;

    Element.prototype.addEventListener = jest.fn((event, callback, options) => {
        originalAddEventListener.call(this, event, callback, options);
    });

    Element.prototype.removeEventListener = jest.fn((event, callback, options) => {
        originalRemoveEventListener.call(this, event, callback, options);
    });

    // Document とWindow のイベントリスナーもモック化
    const docAdd = document.addEventListener;
    const docRemove = document.removeEventListener;
    const winAdd = window.addEventListener;
    const winRemove = window.removeEventListener;

    document.addEventListener = jest.fn((event, callback, options) => {
        docAdd.call(document, event, callback, options);
    });

    document.removeEventListener = jest.fn((event, callback, options) => {
        docRemove.call(document, event, callback, options);
    });

    window.addEventListener = jest.fn((event, callback, options) => {
        winAdd.call(window, event, callback, options);
    });

    window.removeEventListener = jest.fn((event, callback, options) => {
        winRemove.call(window, event, callback, options);
    });

    // クリーンアップ関数を返す
    return () => {
        Element.prototype.addEventListener = originalAddEventListener;
        Element.prototype.removeEventListener = originalRemoveEventListener;
        document.addEventListener = docAdd;
        document.removeEventListener = docRemove;
        window.addEventListener = winAdd;
        window.removeEventListener = winRemove;
    };
}

/**
 * 条件が満たされるまで待機する
 */
export function waitFor(condition, timeout = 5000, interval = 100) {
    return new Promise((resolve, reject) => {
        const startTime = Date.now();

        const check = () => {
            if (condition()) {
                resolve();
                return;
            }

            if (Date.now() - startTime >= timeout) {
                reject(new Error(`waitFor timeout after ${timeout}ms`));
                return;
            }

            setTimeout(check, interval);
        };

        check();
    });
}
