/**
 * Livewire モックファイル
 * Laravel Livewireの主要機能をテスト用にモック化
 */

const mockEvents = {};
const mockComponents = {};

const Livewire = {
    /**
     * イベント発火
     */
    dispatch: jest.fn((event, data) => {
        if (mockEvents[event]) {
            mockEvents[event].forEach(callback => {
                callback(data);
            });
        }
    }),

    /**
     * イベントリスナー
     */
    on: jest.fn((event, callback) => {
        if (!mockEvents[event]) {
            mockEvents[event] = [];
        }
        mockEvents[event].push(callback);
    }),

    /**
     * イベントリスナー削除
     */
    off: jest.fn((event, callback) => {
        if (mockEvents[event]) {
            const index = mockEvents[event].indexOf(callback);
            if (index > -1) {
                mockEvents[event].splice(index, 1);
            }
        }
    }),

    /**
     * コンポーネント検索
     */
    find: jest.fn(componentId => {
        return mockComponents[componentId] || null;
    }),

    /**
     * 全コンポーネント取得
     */
    all: jest.fn(() => {
        return Object.values(mockComponents);
    }),

    /**
     * フック
     */
    hook: jest.fn((name, callback) => {
        // フック機能のモック
    }),

    /**
     * プラグイン
     */
    plugin: jest.fn(pluginFunction => {
        // プラグイン機能のモック
    }),

    /**
     * 開始
     */
    start: jest.fn(),

    /**
     * 停止
     */
    stop: jest.fn(),

    /**
     * アルパインスタートフック
     */
    onAlpineReady: jest.fn(callback => {
        // Alpine.js準備完了のモック
        callback();
    }),

    /**
     * イベントを発火（テスト用）
     */
    _trigger: (event, data) => {
        if (mockEvents[event]) {
            mockEvents[event].forEach(callback => {
                callback(data);
            });
        }
    },

    /**
     * コンポーネントを追加（テスト用）
     */
    _addComponent: (id, component) => {
        mockComponents[id] = component;
    },

    /**
     * コンポーネントを削除（テスト用）
     */
    _removeComponent: id => {
        delete mockComponents[id];
    },

    /**
     * モックをリセット
     */
    _reset: () => {
        Object.keys(mockEvents).forEach(key => delete mockEvents[key]);
        Object.keys(mockComponents).forEach(key => delete mockComponents[key]);

        Livewire.dispatch.mockClear();
        Livewire.on.mockClear();
        Livewire.off.mockClear();
        Livewire.find.mockClear();
        Livewire.all.mockClear();
        Livewire.hook.mockClear();
        Livewire.plugin.mockClear();
        Livewire.start.mockClear();
        Livewire.stop.mockClear();
        Livewire.onAlpineReady.mockClear();
    },
};

// Livewireコンポーネントモック
const mockLivewireComponent = {
    id: 'test-component',
    name: 'TestComponent',

    /**
     * サーバーアクションを呼び出し
     */
    call: jest.fn((method, ...params) => {
        return Promise.resolve({ method, params });
    }),

    /**
     * プロパティ設定
     */
    set: jest.fn((property, value) => {
        mockLivewireComponent.data = mockLivewireComponent.data || {};
        mockLivewireComponent.data[property] = value;
        return Promise.resolve();
    }),

    /**
     * リフレッシュ
     */
    refresh: jest.fn(() => {
        return Promise.resolve();
    }),

    /**
     * データ
     */
    data: {},

    /**
     * モックをリセット
     */
    _reset: () => {
        mockLivewireComponent.data = {};
        mockLivewireComponent.call.mockClear();
        mockLivewireComponent.set.mockClear();
        mockLivewireComponent.refresh.mockClear();
    },
};

// グローバルに設定
global.Livewire = Livewire;

export default Livewire;
export { mockLivewireComponent };
