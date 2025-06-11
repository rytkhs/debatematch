import Logger from '../../services/logger.js';

/**
 * ルーム待機ページでのPusher接続管理を行うクラス
 */
export class RoomConnectionManager {
    constructor(roomId, userId, pusherKey, pusherCluster) {
        this.logger = new Logger('RoomConnectionManager');
        this.roomId = roomId;
        this.userId = userId;
        this.pusherKey = pusherKey;
        this.pusherCluster = pusherCluster;

        this.pusher = null;
        this.channel = null;
        this.presenceChannel = null;

        this.initialize();
    }

    initialize() {
        // Pusherの初期化
        this.pusher = new Pusher(this.pusherKey, {
            cluster: this.pusherCluster,
            authEndpoint: '/pusher/auth',
            encrypted: true
        });

        // チャンネルの購読
        this.channel = this.pusher.subscribe('rooms.' + this.roomId);
        this.presenceChannel = this.pusher.subscribe('presence-room.' + this.roomId);

        this.logger.log('チャンネル初期化:', this.channel);

        // 接続状態の監視
        this.monitorConnectionState();
    }

    monitorConnectionState() {
        this.pusher.connection.bind('state_change', states => {
            if (states.current === 'disconnected' || states.current === 'failed') {
                // 接続切断の処理は別のコンポーネントで処理
                if (window.disconnectionHandler) {
                    window.disconnectionHandler.handleSelfDisconnection();
                }
            } else if (states.current === 'connected' && states.previous === 'disconnected') {
                // 接続復旧の処理
                if (window.disconnectionHandler) {
                    window.disconnectionHandler.hideAlert();
                    window.disconnectionHandler.stopCountdown();
                }
                this.showNotification(window.translations?.connection_restored || 'Connection restored', 'success');
            }
        });
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

    showNotification(message, type = 'info') {
        if (window.showNotification) {
            window.showNotification(message, type);
            return;
        }

        const notification = document.createElement('div');

        const styles = {
            'info': 'bg-blue-100 border-blue-500 text-blue-800',
            'success': 'bg-green-100 border-green-500 text-green-800',
            'warning': 'bg-yellow-100 border-yellow-500 text-yellow-800',
            'error': 'bg-red-100 border-red-500 text-red-800'
        };

        notification.className = `fixed rounded-lg shadow-lg border-l-4 ${styles[type] || styles.info}`;
        notification.style.top = '1rem';
        notification.style.right = '1rem';
        notification.style.padding = '0.75rem 1.5rem';
        notification.style.zIndex = '50';
        notification.style.transition = 'all 0.5s ease';
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        notification.innerText = message;

        document.body.appendChild(notification);

        // 強制的にレイアウト計算を行う
        notification.getBoundingClientRect();

        // アニメーション
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        }, 10);

        // 3秒後に消す
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';

            // トランジション終了後に要素を削除
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }
}
