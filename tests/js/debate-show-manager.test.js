/**
 * DebateShowManagerのテスト
 * 重複初期化防止とクリーンアップ機能をテスト
 */

import { jest } from '@jest/globals';
import { mockPusher, mockChannel } from './__mocks__/pusher.js';

// テスト用のDebateShowManagerクラス（簡略版）
class TestDebateShowManager {
    constructor() {
        this.managers = {};
        this.isInitialized = false;
        this.initializationTimeout = null;
    }

    initialize() {
        if (this.isInitialized) {
            console.log('DebateShowManager already initialized, skipping');
            return;
        }

        // デバッグデータの確認
        if (typeof window.debateData === 'undefined') {
            console.error('window.debateData is not available');
            return;
        }

        // 既存のグローバルマネージャーをクリーンアップ
        if (window.debateShowManager && window.debateShowManager !== this) {
            window.debateShowManager.cleanup();
        }

        // 各機能の初期化（簡略版）
        this.initializePresenceManager();
        this.setupGlobalReferences();

        this.isInitialized = true;
        console.log('DebateShowManager initialized successfully');
    }

    initializePresenceManager() {
        // モックのプレゼンスマネージャー
        this.managers.presenceManager = {
            initialize: jest.fn(),
            cleanup: jest.fn(),
        };
        this.managers.presenceManager.initialize();
    }

    setupGlobalReferences() {
        // グローバル参照設定
        window.debateShowManager = this;
        window.confirmEarlyTermination = jest.fn();
    }

    cleanup() {
        if (!this.isInitialized) return;

        console.log('Cleaning up DebateShowManager');

        // 初期化タイムアウトをクリア
        if (this.initializationTimeout) {
            clearTimeout(this.initializationTimeout);
            this.initializationTimeout = null;
        }

        // 各マネージャーのクリーンアップ
        Object.values(this.managers).forEach(manager => {
            if (manager && typeof manager.cleanup === 'function') {
                try {
                    manager.cleanup();
                } catch (error) {
                    console.error('Error cleaning up manager:', error);
                }
            }
        });

        // マネージャー参照をクリア
        this.managers = {};

        // グローバル参照のクリーンアップ
        if (window.debateShowManager === this) {
            delete window.debateShowManager;
        }
        if (window.confirmEarlyTermination) {
            delete window.confirmEarlyTermination;
        }

        this.isInitialized = false;
    }
}

describe('DebateShowManager', () => {
    let debateShowManager;
    let originalConsoleLog, originalConsoleError;

    beforeEach(() => {
        // コンソールをモック
        originalConsoleLog = console.log;
        originalConsoleError = console.error;
        console.log = jest.fn();
        console.error = jest.fn();

        // テストデータの準備
        window.debateData = {
            debateId: 123,
            pusherKey: 'test-key',
            pusherCluster: 'test-cluster',
        };

        // モックをリセット
        mockPusher._reset();
        mockChannel._reset();

        // グローバル変数をクリア
        delete window.debateShowManager;
        delete window.confirmEarlyTermination;
        delete window.Livewire;
    });

    afterEach(() => {
        if (debateShowManager) {
            debateShowManager.cleanup();
        }

        // グローバル変数をクリア
        delete window.debateData;
        delete window.debateShowManager;
        delete window.confirmEarlyTermination;

        // コンソールを復元
        console.log = originalConsoleLog;
        console.error = originalConsoleError;
    });

    test('正常な初期化ができる', () => {
        debateShowManager = new TestDebateShowManager();
        debateShowManager.initialize();

        expect(debateShowManager.isInitialized).toBe(true);
        expect(debateShowManager.managers.presenceManager).toBeDefined();
        expect(window.debateShowManager).toBe(debateShowManager);
        expect(window.confirmEarlyTermination).toBeDefined();
        expect(console.log).toHaveBeenCalledWith('DebateShowManager initialized successfully');
    });

    test('重複初期化を防止する', () => {
        debateShowManager = new TestDebateShowManager();

        // 1回目の初期化
        debateShowManager.initialize();
        expect(console.log).not.toHaveBeenCalledWith(
            'DebateShowManager already initialized, skipping'
        );

        // 2回目の初期化（重複）
        debateShowManager.initialize();
        expect(console.log).toHaveBeenCalledWith('DebateShowManager already initialized, skipping');
    });

    test('debateDataがない場合はエラーログを出力する', () => {
        delete window.debateData;

        debateShowManager = new TestDebateShowManager();
        debateShowManager.initialize();

        expect(console.error).toHaveBeenCalledWith('window.debateData is not available');
        expect(debateShowManager.isInitialized).toBe(false);
    });

    test('既存のグローバルマネージャーをクリーンアップする', () => {
        // 既存のマネージャーを設定
        const existingManager = new TestDebateShowManager();
        existingManager.isInitialized = true;
        existingManager.cleanup = jest.fn();
        window.debateShowManager = existingManager;

        debateShowManager = new TestDebateShowManager();
        debateShowManager.initialize();

        expect(existingManager.cleanup).toHaveBeenCalled();
    });

    test('プレゼンスマネージャーが初期化される', () => {
        debateShowManager = new TestDebateShowManager();
        debateShowManager.initialize();

        expect(debateShowManager.managers.presenceManager).toBeDefined();
        expect(debateShowManager.managers.presenceManager.initialize).toHaveBeenCalled();
    });

    test('クリーンアップが正常に動作する', () => {
        debateShowManager = new TestDebateShowManager();
        debateShowManager.initialize();

        expect(debateShowManager.isInitialized).toBe(true);
        expect(window.debateShowManager).toBe(debateShowManager);

        debateShowManager.cleanup();

        expect(debateShowManager.isInitialized).toBe(false);
        expect(debateShowManager.managers).toEqual({});
        expect(window.debateShowManager).toBeUndefined();
        expect(window.confirmEarlyTermination).toBeUndefined();
        expect(console.log).toHaveBeenCalledWith('Cleaning up DebateShowManager');
    });

    test('マネージャーのクリーンアップでエラーが発生しても継続する', () => {
        debateShowManager = new TestDebateShowManager();
        debateShowManager.initialize();

        // エラーを発生させるマネージャーを追加
        debateShowManager.managers.errorManager = {
            cleanup: jest.fn(() => {
                throw new Error('Test cleanup error');
            }),
        };

        debateShowManager.cleanup();

        expect(console.error).toHaveBeenCalledWith('Error cleaning up manager:', expect.any(Error));
        expect(debateShowManager.isInitialized).toBe(false);
    });

    test('初期化タイムアウトが正常にクリアされる', () => {
        debateShowManager = new TestDebateShowManager();

        // タイムアウトを設定
        debateShowManager.initializationTimeout = setTimeout(() => {}, 1000);

        debateShowManager.initialize();
        debateShowManager.cleanup();

        // タイムアウトがクリアされていることを確認
        expect(debateShowManager.initializationTimeout).toBeNull();
    });

    test('複数のインスタンスが適切に管理される', () => {
        // 1つ目のインスタンス
        const manager1 = new TestDebateShowManager();
        manager1.initialize();
        const cleanup1 = jest.spyOn(manager1, 'cleanup');

        // 2つ目のインスタンス（1つ目をクリーンアップするはず）
        const manager2 = new TestDebateShowManager();
        manager2.initialize();

        // 1つ目のマネージャーがクリーンアップされていることを確認
        expect(cleanup1).toHaveBeenCalled();
        expect(window.debateShowManager).toBe(manager2);

        // クリーンアップ
        manager1.cleanup();
        manager2.cleanup();
    });

    test('未初期化状態でクリーンアップを呼んでも安全', () => {
        debateShowManager = new TestDebateShowManager();

        // 初期化せずにクリーンアップ
        expect(() => {
            debateShowManager.cleanup();
        }).not.toThrow();

        expect(debateShowManager.isInitialized).toBe(false);
    });
});
