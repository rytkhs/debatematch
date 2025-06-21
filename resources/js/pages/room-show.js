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
        this.eventHandler = null;
        this.uiManager = null;
        this.heartbeatService = null;

        this.initialize();
    }

    initialize() {
        this.uiManager = new RoomUIManager();
        this.eventHandler = new RoomEventHandler(this.roomId, this.userId);
        this.initializeHeartbeat();
    }

    initializeHeartbeat() {
        this.heartbeatService = new HeartbeatService({
            contextType: 'room',
            contextId: this.roomId,
        });
        setTimeout(() => this.heartbeatService.start(), 30000);
    }

    cleanup() {
        if (this.eventHandler) {
            this.eventHandler.cleanup();
        }
        if (this.heartbeatService) {
            this.heartbeatService.stop();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (!window.roomData) {
        console.error('roomData is not defined');
        return;
    }
    const roomManager = new RoomShowManager(window.roomData);

    window.addEventListener('beforeunload', () => {
        roomManager.cleanup();
    });
});
