/**
 * RoomConnectionManagerのテスト
 * 重複初期化防止とエラーハンドリング機能をテスト
 */

import { jest } from '@jest/globals';
import { mockPusher, mockChannel } from './__mocks__/pusher.js';

// テスト用のRoomConnectionManagerクラス（簡略版）
class TestRoomConnectionManager {
    constructor(roomId, userId, pusherKey, pusherCluster) {
        this.roomId = roomId;
        this.userId = userId;
        this.pusherKey = pusherKey;
        this.pusherCluster = pusherCluster;
        this.pusher = null;
        this.channel = null;
        this.presenceChannel = null;
        this.isInitialized = false;
        this.connectionAttempts = 0;
        this.maxConnectionAttempts = 3;
        this.connectionTimeout = null;
        this.logger = {
            log: jest.fn(),
            error: jest.fn(),
        };
    }

    initialize() {
        if (this.isInitialized) {
            this.logger.log('Already initialized, skipping');
            return;
        }

        this.cleanupExistingConnections();

        try {
            this.connectionTimeout = setTimeout(() => {
                this.logger.error('Room Pusher connection timeout');
                this.handleConnectionError(new Error('Connection timeout'));
            }, 15000);

            this.pusher = new mockPusher(this.pusherKey, {
                cluster: this.pusherCluster,
                authEndpoint: '/pusher/auth',
                encrypted: true,
                forceTLS: true,
            });

            window.roomPusherInstance = this.pusher;
            this.subscribeToChannels();
            this.isInitialized = true;
        } catch (error) {
            this.logger.error('Room Pusher initialization error:', error);
            this.handleConnectionError(error);
        }
    }

    cleanupExistingConnections() {
        if (window.roomPusherInstance) {
            try {
                window.roomPusherInstance.disconnect();
                delete window.roomPusherInstance;
                this.logger.log('Cleaned up existing room Pusher connection');
            } catch (error) {
                this.logger.error('Error cleaning up existing room connection:', error);
            }
        }
    }

    subscribeToChannels() {
        try {
            this.channel = this.pusher.subscribe('rooms.' + this.roomId);
            this.presenceChannel = this.pusher.subscribe('presence-room.' + this.roomId);
            this.logger.log('チャンネル初期化:', this.channel);
        } catch (error) {
            this.logger.error('Channel subscription error:', error);
            this.handleConnectionError(error);
        }
    }

    handleConnectionError(error) {
        if (this.connectionTimeout) {
            clearTimeout(this.connectionTimeout);
            this.connectionTimeout = null;
        }

        this.connectionAttempts++;
        this.logger.error(`Room connection attempt ${this.connectionAttempts} failed:`, error);

        if (this.connectionAttempts >= this.maxConnectionAttempts) {
            this.logger.error('Max room connection attempts reached');
            return;
        }

        this.logger.log(
            `Would retry room connection in ${Math.pow(2, this.connectionAttempts)} seconds`
        );
    }

    getChannel() {
        return this.channel;
    }

    getPresenceChannel() {
        return this.presenceChannel;
    }

    getPusher() {
        return this.pusher;
    }

    cleanup() {
        if (this.connectionTimeout) {
            clearTimeout(this.connectionTimeout);
            this.connectionTimeout = null;
        }

        if (this.channel) {
            try {
                this.pusher.unsubscribe('rooms.' + this.roomId);
            } catch (error) {
                this.logger.error('Error unsubscribing from room channel:', error);
            }
            this.channel = null;
        }

        if (this.presenceChannel) {
            try {
                this.pusher.unsubscribe('presence-room.' + this.roomId);
            } catch (error) {
                this.logger.error('Error unsubscribing from presence channel:', error);
            }
            this.presenceChannel = null;
        }

        if (this.pusher) {
            try {
                this.pusher.disconnect();
            } catch (error) {
                this.logger.error('Error disconnecting room Pusher:', error);
            }
            this.pusher = null;
        }

        if (window.roomPusherInstance === this.pusher) {
            delete window.roomPusherInstance;
        }

        this.isInitialized = false;
        this.connectionAttempts = 0;
    }
}

describe('RoomConnectionManager', () => {
    let roomConnectionManager;
    const roomId = 'test-room-123';
    const userId = 'test-user-456';
    const pusherKey = 'test-pusher-key';
    const pusherCluster = 'test-cluster';

    beforeEach(() => {
        // モックをリセット
        mockPusher._reset();
        mockChannel._reset();

        // グローバル変数をクリア
        delete window.roomPusherInstance;
        delete window.disconnectionHandler;
    });

    afterEach(() => {
        if (roomConnectionManager) {
            roomConnectionManager.cleanup();
        }
        // グローバル変数をクリア
        delete window.roomPusherInstance;
    });

    test('正常な初期化ができる', () => {
        roomConnectionManager = new TestRoomConnectionManager(
            roomId,
            userId,
            pusherKey,
            pusherCluster
        );
        roomConnectionManager.initialize();

        expect(roomConnectionManager.isInitialized).toBe(true);
        expect(roomConnectionManager.pusher).toBeDefined();
        expect(roomConnectionManager.channel).toBeDefined();
        expect(roomConnectionManager.presenceChannel).toBeDefined();
        expect(window.roomPusherInstance).toBeDefined();
    });

    test('重複初期化を防止する', () => {
        roomConnectionManager = new TestRoomConnectionManager(
            roomId,
            userId,
            pusherKey,
            pusherCluster
        );

        // 1回目の初期化
        roomConnectionManager.initialize();
        expect(roomConnectionManager.logger.log).not.toHaveBeenCalledWith(
            'Already initialized, skipping'
        );

        // 2回目の初期化（重複）
        roomConnectionManager.initialize();
        expect(roomConnectionManager.logger.log).toHaveBeenCalledWith(
            'Already initialized, skipping'
        );
    });

    test('既存のルーム接続をクリーンアップする', () => {
        // 既存の接続を設定
        const existingRoomPusher = { disconnect: jest.fn() };
        window.roomPusherInstance = existingRoomPusher;

        roomConnectionManager = new TestRoomConnectionManager(
            roomId,
            userId,
            pusherKey,
            pusherCluster
        );
        roomConnectionManager.initialize();

        expect(existingRoomPusher.disconnect).toHaveBeenCalled();
        expect(roomConnectionManager.logger.log).toHaveBeenCalledWith(
            'Cleaned up existing room Pusher connection'
        );
    });

    test('チャンネル購読が正常に動作する', () => {
        roomConnectionManager = new TestRoomConnectionManager(
            roomId,
            userId,
            pusherKey,
            pusherCluster
        );
        roomConnectionManager.initialize();

        // チャンネル購読の確認
        expect(roomConnectionManager.pusher.subscribe).toHaveBeenCalledWith('rooms.' + roomId);
        expect(roomConnectionManager.pusher.subscribe).toHaveBeenCalledWith(
            'presence-room.' + roomId
        );
        expect(roomConnectionManager.logger.log).toHaveBeenCalledWith(
            'チャンネル初期化:',
            expect.any(Object)
        );
    });

    test('接続エラー時に再試行制御が動作する', () => {
        roomConnectionManager = new TestRoomConnectionManager(
            roomId,
            userId,
            pusherKey,
            pusherCluster
        );

        const error = new Error('Test room connection error');
        roomConnectionManager.handleConnectionError(error);

        expect(roomConnectionManager.connectionAttempts).toBe(1);
        expect(roomConnectionManager.logger.error).toHaveBeenCalledWith(
            'Room connection attempt 1 failed:',
            error
        );
        expect(roomConnectionManager.logger.log).toHaveBeenCalledWith(
            'Would retry room connection in 2 seconds'
        );
    });

    test('最大再試行回数に達したら諦める', () => {
        roomConnectionManager = new TestRoomConnectionManager(
            roomId,
            userId,
            pusherKey,
            pusherCluster
        );

        // 最大回数まで試行
        for (let i = 0; i < roomConnectionManager.maxConnectionAttempts; i++) {
            roomConnectionManager.handleConnectionError(new Error('Test error'));
        }

        expect(roomConnectionManager.connectionAttempts).toBe(3);
        expect(roomConnectionManager.logger.error).toHaveBeenCalledWith(
            'Max room connection attempts reached'
        );
    });

    test('チャンネルゲッターが正常に動作する', () => {
        roomConnectionManager = new TestRoomConnectionManager(
            roomId,
            userId,
            pusherKey,
            pusherCluster
        );
        roomConnectionManager.initialize();

        expect(roomConnectionManager.getChannel()).toBe(roomConnectionManager.channel);
        expect(roomConnectionManager.getPresenceChannel()).toBe(
            roomConnectionManager.presenceChannel
        );
        expect(roomConnectionManager.getPusher()).toBe(roomConnectionManager.pusher);
    });

    test('クリーンアップが正常に動作する', () => {
        roomConnectionManager = new TestRoomConnectionManager(
            roomId,
            userId,
            pusherKey,
            pusherCluster
        );
        roomConnectionManager.initialize();

        const pusherInstance = roomConnectionManager.pusher;
        expect(roomConnectionManager.isInitialized).toBe(true);

        roomConnectionManager.cleanup();

        expect(roomConnectionManager.isInitialized).toBe(false);
        expect(roomConnectionManager.connectionAttempts).toBe(0);
        expect(roomConnectionManager.pusher).toBeNull();
        expect(roomConnectionManager.channel).toBeNull();
        expect(roomConnectionManager.presenceChannel).toBeNull();
        expect(pusherInstance.disconnect).toHaveBeenCalled();
        expect(pusherInstance.unsubscribe).toHaveBeenCalledWith('rooms.' + roomId);
        expect(pusherInstance.unsubscribe).toHaveBeenCalledWith('presence-room.' + roomId);
    });

    test('複数のルーム接続インスタンスが適切に管理される', () => {
        // 1つ目のインスタンス
        const manager1 = new TestRoomConnectionManager(roomId, userId, pusherKey, pusherCluster);
        manager1.initialize();

        // 2つ目のインスタンス（1つ目をクリーンアップするはず）
        const manager2 = new TestRoomConnectionManager(
            'room-456',
            userId,
            pusherKey,
            pusherCluster
        );
        manager2.initialize();

        // 1つ目のPusherがクリーンアップされていることを確認
        expect(manager2.logger.log).toHaveBeenCalledWith(
            'Cleaned up existing room Pusher connection'
        );

        // クリーンアップ
        manager1.cleanup();
        manager2.cleanup();
    });
});
