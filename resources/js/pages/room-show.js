import { RoomConnectionManager } from '../features/room/connection-manager.js';
import { RoomEventHandler } from '../features/room/event-handler.js';
import { RoomUIManager } from '../features/room/ui-manager.js';
import HeartbeatService from '../services/heartbeat.js';

/**
 * ルーム待機ページのメインマネージャークラス
 */
class RoomShowManager {
    constructor(options) {
        this.roomId = options.roomId;
        this.userId = options.authUserId;
        this.isCreator = options.isCreator;
        this.pusherKey = options.pusherKey;
        this.pusherCluster = options.pusherCluster;

        this.connectionManager = null;
        this.eventHandler = null;
        this.uiManager = null;
        this.heartbeatService = null;

        this.initialize();
    }

    initialize() {
        // UI管理機能の初期化
        this.uiManager = new RoomUIManager();

        // Pusher接続管理の初期化
        this.connectionManager = new RoomConnectionManager(
            this.roomId,
            this.userId,
            this.pusherKey,
            this.pusherCluster
        );

        // イベントハンドラーの初期化
        this.eventHandler = new RoomEventHandler(
            this.roomId,
            this.userId,
            this.connectionManager.getChannel(),
            this.connectionManager.getPresenceChannel()
        );

        // ハートビートサービスの初期化
        this.initializeHeartbeat();

        // グローバル参照設定（互換性維持）
        this.setupGlobalReferences();
    }

    initializeHeartbeat() {
        this.heartbeatService = new HeartbeatService({
            contextType: 'room',
            contextId: this.roomId,
        });

        // 30秒後にハートビートを開始
        setTimeout(() => {
            this.heartbeatService.start();
        }, 30000);
    }

    setupGlobalReferences() {
        // 既存コードとの互換性のため、グローバル参照を設定
        // eventHandlerの存在チェック
        if (!this.eventHandler) {
            console.warn('eventHandler is not initialized yet');
            return;
        }

        window.roomManager = {
            roomId: this.roomId,
            userId: this.userId,
            pusher: this.connectionManager.getPusher(),
            channel: this.connectionManager.getChannel(),
            presenceChannel: this.connectionManager.getPresenceChannel(),
            showNotification: this.eventHandler.showNotification.bind(this.eventHandler),
            showLoadingCountdown: this.eventHandler.showLoadingCountdown.bind(this.eventHandler),
        };

        window.heartbeatService = this.heartbeatService;
    }
}

// DOMロード時に初期化
document.addEventListener('DOMContentLoaded', () => {
    if (!window.roomData) {
        console.error('roomData is not defined');
        return;
    }

    new RoomShowManager(window.roomData);
});
