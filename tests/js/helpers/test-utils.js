/**
 * 軽量テストヘルパーユーティリティ
 */

/**
 * DOMエレメント作成ヘルパー
 */
export const createElement = (tag, attributes = {}, content = '') => {
    const element = document.createElement(tag);
    Object.entries(attributes).forEach(([key, value]) => {
        element.setAttribute(key, value);
    });
    if (content) {
        element.textContent = content;
    }
    return element;
};

/**
 * イベントシミュレーションヘルパー
 */
export const simulateEvent = (element, eventType, eventData = {}) => {
    const event = new Event(eventType, { bubbles: true, cancelable: true });
    Object.entries(eventData).forEach(([key, value]) => {
        event[key] = value;
    });
    element.dispatchEvent(event);
    return event;
};

/**
 * 非同期処理待機ヘルパー
 */
export const waitFor = (conditionFn, timeout = 1000, interval = 50) => {
    return new Promise((resolve, reject) => {
        const startTime = Date.now();

        const check = () => {
            if (conditionFn()) {
                resolve();
            } else if (Date.now() - startTime > timeout) {
                reject(new Error('Timeout waiting for condition'));
            } else {
                setTimeout(check, interval);
            }
        };

        check();
    });
};

/**
 * 軽量なAlpine.jsモック
 */
export const createAlpineMock = () => ({
    data: jest.fn(fn => fn()),
    directive: jest.fn(),
    magic: jest.fn(),
    start: jest.fn(),
    stop: jest.fn(),
    version: '3.x',
});

/**
 * 簡単なフォームデータ作成
 */
export const createFormData = data => {
    const formData = new FormData();
    Object.entries(data).forEach(([key, value]) => {
        formData.append(key, value);
    });
    return formData;
};

/**
 * ローカルストレージモック
 */
export const createLocalStorageMock = () => {
    let store = {};

    return {
        getItem: jest.fn(key => store[key] || null),
        setItem: jest.fn((key, value) => {
            store[key] = value.toString();
        }),
        removeItem: jest.fn(key => {
            delete store[key];
        }),
        clear: jest.fn(() => {
            store = {};
        }),
        get length() {
            return Object.keys(store).length;
        },
        key: jest.fn(index => Object.keys(store)[index] || null),
    };
};

/**
 * URLパラメータモック
 */
export const mockURLSearchParams = (params = {}) => {
    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        searchParams.append(key, value);
    });

    // URLSearchParamsをモック
    global.URLSearchParams = jest.fn(() => searchParams);

    return searchParams;
};

/**
 * Console出力キャプチャ
 */
export const captureConsole = () => {
    const originalLog = console.log;
    const originalError = console.error;
    const originalWarn = console.warn;

    const logs = [];

    console.log = jest.fn((...args) => {
        logs.push({ type: 'log', args });
    });

    console.error = jest.fn((...args) => {
        logs.push({ type: 'error', args });
    });

    console.warn = jest.fn((...args) => {
        logs.push({ type: 'warn', args });
    });

    return {
        logs,
        restore: () => {
            console.log = originalLog;
            console.error = originalError;
            console.warn = originalWarn;
        },
    };
};

/**
 * タイマーヘルパー
 */
export const advanceTimersAsync = async ms => {
    jest.advanceTimersByTime(ms);
    await Promise.resolve(); // 非同期処理の完了を待つ
};

/**
 * エラーハンドリングヘルパー
 */
export const expectToThrow = (fn, errorMessage = undefined) => {
    expect(fn).toThrow(errorMessage);
};

/**
 * デバッグヘルパー（開発時のみ使用）
 */
export const debugElement = element => {
    if (process.env.NODE_ENV === 'development') {
        console.log('Debug Element:', {
            tag: element.tagName,
            classes: Array.from(element.classList),
            attributes: Array.from(element.attributes).map(attr => `${attr.name}="${attr.value}"`),
            content: element.textContent,
            innerHTML: element.innerHTML,
        });
    }
};
