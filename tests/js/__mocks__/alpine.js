/**
 * Alpine.js モックファイル
 * テスト環境でAlpine.jsの主要メソッドをモック化
 */

const mockStore = {};
const mockDirectives = {};

const Alpine = {
    /**
     * データストアのモック
     */
    store: jest.fn((name, data) => {
        if (data) {
            mockStore[name] = data;
            return data;
        }
        return mockStore[name];
    }),

    /**
     * データ関数のモック
     */
    data: jest.fn((name, callback) => {
        const dataObj = typeof callback === 'function' ? callback() : callback;
        return dataObj;
    }),

    /**
     * ディレクティブのモック
     */
    directive: jest.fn((name, callback) => {
        mockDirectives[name] = callback;
    }),

    /**
     * Alpineの開始モック
     */
    start: jest.fn(),

    /**
     * プラグインモック
     */
    plugin: jest.fn(),

    /**
     * マジックメソッドのモック
     */
    magic: jest.fn(),

    /**
     * 内部ストアとディレクティブへのアクセス（テスト用）
     */
    _mockStore: mockStore,
    _mockDirectives: mockDirectives,

    /**
     * モックをリセット
     */
    _reset: () => {
        Object.keys(mockStore).forEach(key => delete mockStore[key]);
        Object.keys(mockDirectives).forEach(key => delete mockDirectives[key]);
        Alpine.store.mockClear();
        Alpine.data.mockClear();
        Alpine.directive.mockClear();
        Alpine.start.mockClear();
        Alpine.plugin.mockClear();
        Alpine.magic.mockClear();
    },

    /**
     * モックをリセット（後方互換性のため）
     */
    _resetMocks: () => Alpine._reset(),
};

// グローバルに設定
global.Alpine = Alpine;

export default Alpine;
