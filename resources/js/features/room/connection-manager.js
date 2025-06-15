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
        this.isInitialized = false;
        this.connectionAttempts = 0;
        this.maxConnectionAttempts = 3;
        this.connectionTimeout = null;

        this.initialize();
    }

    initialize() {
        // 既に初期化済みの場合は処理をスキップ
        if (this.isInitialized) {
            this.logger.log('Already initialized, skipping');
            return;
        }

        // 既存のルーム用Pusher接続をクリーンアップ
        this.cleanupExistingConnections();

        try {
            // 接続タイムアウトを設定
            this.connectionTimeout = setTimeout(() => {
                this.logger.error('Room Pusher connection timeout');
                this.handleConnectionError(new Error('Connection timeout'));
            }, 15000);

            // Pusherの初期化
            this.pusher = new Pusher(this.pusherKey, {
                cluster: this.pusherCluster,
                authEndpoint: '/pusher/auth',
                encrypted: true,
                forceTLS: true,
                enabledTransports: ['ws', 'wss'],
                auth: {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
                activityTimeout: 120000,
                pongTimeout: 30000,
            });

            // グローバル参照を設定（ルーム用）
            window.roomPusherInstance = this.pusher;

            // チャンネルの購読（エラーハンドリング付き）
            this.subscribeToChannels();

            this.logger.log('Room Pusher initialized successfully');

            // 接続状態の監視
            this.monitorConnectionState();

            this.isInitialized = true;
        } catch (error) {
            this.logger.error('Room Pusher initialization error:', error);
            this.handleConnectionError(error);
        }
    }

    /**
     * 既存のルーム用Pusher接続をクリーンアップ
     */
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

    /**
     * チャンネルに購読
     */
    subscribeToChannels() {
        try {
            this.channel = this.pusher.subscribe('rooms.' + this.roomId);
            this.presenceChannel = this.pusher.subscribe('presence-room.' + this.roomId);

            this.logger.log('チャンネル初期化:', this.channel);

            // 購読成功イベント
            this.channel.bind('pusher:subscription_succeeded', () => {
                if (this.connectionTimeout) {
                    clearTimeout(this.connectionTimeout);
                    this.connectionTimeout = null;
                }
                this.connectionAttempts = 0;
                this.logger.log('Room channel subscription succeeded');
            });

            // 購読エラーイベント
            this.channel.bind('pusher:subscription_error', error => {
                this.logger.error('Room channel subscription error:', error);
                this.handleConnectionError(error);
            });

            this.presenceChannel.bind('pusher:subscription_succeeded', () => {
                this.logger.log('Room presence channel subscription succeeded');
            });

            this.presenceChannel.bind('pusher:subscription_error', error => {
                this.logger.error('Room presence channel subscription error:', error);
                this.handleConnectionError(error);
            });
        } catch (error) {
            this.logger.error('Channel subscription error:', error);
            this.handleConnectionError(error);
        }
    }

    /**
     * 接続エラーハンドリング
     */
    handleConnectionError(error) {
        if (this.connectionTimeout) {
            clearTimeout(this.connectionTimeout);
            this.connectionTimeout = null;
        }

        this.connectionAttempts++;
        this.logger.error(`Room connection attempt ${this.connectionAttempts} failed:`, error);

        // 最大試行回数に達した場合
        if (this.connectionAttempts >= this.maxConnectionAttempts) {
            this.logger.error('Max room connection attempts reached');
            this.showNotification('接続に失敗しました。ページを再読み込みしてください。', 'error');
            return;
        }

        // 再試行
        const retryDelay = Math.pow(2, this.connectionAttempts) * 1000;
        this.logger.log(`Retrying room connection in ${retryDelay / 1000} seconds...`);

        setTimeout(() => {
            this.cleanup();
            this.isInitialized = false;
            this.initialize();
        }, retryDelay);
    }

    monitorConnectionState() {
        if (!this.pusher) return;

        this.pusher.connection.bind('state_change', states => {
            this.logger.log(
                `Room connection state changed: ${states.previous} -> ${states.current}`
            );

            if (states.current === 'disconnected' || states.current === 'failed') {
                // 接続切断の処理は別のコンポーネントで処理
                if (window.disconnectionHandler) {
                    window.disconnectionHandler.handleSelfDisconnection();
                }
            } else if (states.current === 'connected' && states.previous === 'disconnected') {
                // 接続復旧の処理
                this.connectionAttempts = 0; // 成功したためリセット
                if (window.disconnectionHandler) {
                    window.disconnectionHandler.hideAlert();
                    window.disconnectionHandler.stopCountdown();
                }
                this.showNotification(
                    window.translations?.connection_restored || 'Connection restored',
                    'success'
                );
            } else if (states.current === 'connected') {
                this.connectionAttempts = 0; // 接続成功
            }
        });

        // エラーイベント
        this.pusher.connection.bind('error', error => {
            this.logger.error('Room Pusher connection error:', error);
            this.handleConnectionError(error);
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

    /**
     * リソースをクリーンアップ
     */
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

        // グローバル参照をクリア
        if (window.roomPusherInstance === this.pusher) {
            delete window.roomPusherInstance;
        }

        this.isInitialized = false;
        this.connectionAttempts = 0;
    }

    showNotification(message, type = 'info') {
        if (window.showNotification) {
            window.showNotification(message, type);
            return;
        }

        const notification = document.createElement('div');

        const styles = {
            info: 'bg-blue-100 border-blue-500 text-blue-800',
            success: 'bg-green-100 border-green-500 text-green-800',
            warning: 'bg-yellow-100 border-yellow-500 text-yellow-800',
            error: 'bg-red-100 border-red-500 text-red-800',
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
