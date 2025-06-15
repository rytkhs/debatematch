import HeartbeatService from '../../services/heartbeat.js';
import Logger from '../../services/logger.js';

/**
 * ディベートのプレゼンス管理
 * オンラインステータスと接続管理を担当
 */
class DebatePresenceManager {
    constructor(debateData) {
        this.logger = new Logger('DebatePresenceManager');
        this.debateData = debateData;
        this.pusher = null;
        this.channel = null;
        this.heartbeatService = null;
        this.offlineTimeout = null;
        this.isInitialized = false;
        this.connectionAttempts = 0;
        this.maxConnectionAttempts = 3;
        this.connectionTimeout = null;
    }

    /**
     * プレゼンス管理を初期化
     */
    initialize() {
        if (this.isInitialized) {
            return;
        }

        // グローバルデータの確認
        if (!this.debateData) {
            this.logger.error('Debate data not available');
            return;
        }

        // 既存のグローバルPusher接続をクリーンアップ
        this.cleanupExistingConnections();

        this.initializePusher();
        this.initializeHeartbeat();
        this.setupEventListeners();

        this.isInitialized = true;
    }

    /**
     * 既存のPusher接続をクリーンアップ
     */
    cleanupExistingConnections() {
        // 既存のグローバルPusher接続があればクリーンアップ
        if (window.pusherInstance) {
            try {
                window.pusherInstance.disconnect();
                delete window.pusherInstance;
                this.logger.log('Cleaned up existing global Pusher connection');
            } catch (error) {
                this.logger.error('Error cleaning up existing connection:', error);
            }
        }
    }

    /**
     * Pusherを初期化
     */
    initializePusher() {
        try {
            // 接続タイムアウトを設定
            this.connectionTimeout = setTimeout(() => {
                this.logger.error('Pusher connection timeout');
                this.handleConnectionError(new Error('Connection timeout'));
            }, 15000); // 15秒でタイムアウト

            // Pusher初期化
            this.pusher = new Pusher(this.debateData.pusherKey, {
                cluster: this.debateData.pusherCluster,
                authEndpoint: '/pusher/auth',
                encrypted: true,
                forceTLS: true,
                enabledTransports: ['ws', 'wss'],
                disabledTransports: [],
                // 認証リクエストの制限
                auth: {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
                // 接続タイムアウト設定
                activityTimeout: 120000,
                pongTimeout: 30000,
            });

            // グローバル参照を設定（重複防止のため）
            window.pusherInstance = this.pusher;

            // プレゼンスチャンネル登録（エラーハンドリング付き）
            try {
                this.channel = this.pusher.subscribe(`presence-debate.${this.debateData.debateId}`);
                this.setupPresenceEvents();
                this.setupConnectionEvents();
            } catch (subscribeError) {
                this.logger.error('Channel subscription error:', subscribeError);
                this.handleConnectionError(subscribeError);
            }
        } catch (error) {
            this.logger.error('Pusher initialization error:', error);
            this.handleConnectionError(error);
        }
    }

    /**
     * 接続エラーハンドリング
     */
    handleConnectionError(error) {
        // タイムアウトをクリア
        if (this.connectionTimeout) {
            clearTimeout(this.connectionTimeout);
            this.connectionTimeout = null;
        }

        this.connectionAttempts++;
        this.logger.error(`Connection attempt ${this.connectionAttempts} failed:`, error);

        // 最大試行回数に達した場合
        if (this.connectionAttempts >= this.maxConnectionAttempts) {
            this.logger.error('Max connection attempts reached, giving up');
            // Livewireに接続失敗を通知
            if (window.Livewire) {
                window.Livewire.dispatch('pusher-connection-failed');
            }
            return;
        }

        // 再試行（指数バックオフ）
        const retryDelay = Math.pow(2, this.connectionAttempts) * 1000; // 2^n秒
        this.logger.log(`Retrying connection in ${retryDelay / 1000} seconds...`);

        setTimeout(() => {
            this.cleanup();
            this.isInitialized = false;
            this.initializePusher();
        }, retryDelay);
    }

    /**
     * プレゼンスイベントを設定
     */
    setupPresenceEvents() {
        if (!this.channel) return;

        // オンラインメンバーの初期リスト
        this.channel.bind('pusher:subscription_succeeded', members => {
            // タイムアウトをクリア（成功したため）
            if (this.connectionTimeout) {
                clearTimeout(this.connectionTimeout);
                this.connectionTimeout = null;
            }

            this.logger.log('Pusher subscription succeeded');
            this.connectionAttempts = 0; // 成功したためリセット

            if (members && typeof members.each === 'function') {
                members.each(member => {
                    // リロード対策
                    setTimeout(() => {
                        if (window.Livewire) {
                            window.Livewire.dispatch('member-online', { data: member });
                        }
                    }, 300);
                });
            }
        });

        // チャンネル認証エラー
        this.channel.bind('pusher:subscription_error', error => {
            this.logger.error('Channel subscription error:', error);
            this.handleConnectionError(error);
        });

        // メンバー参加イベント
        this.channel.bind('pusher:member_added', member => {
            this.logger.log('Member joined:', member.info?.name || 'Unknown');
            clearTimeout(this.offlineTimeout); // 既存のオフラインタイマーをクリア
            // ユーザーがオンラインになったとき
            if (window.Livewire) {
                window.Livewire.dispatch('member-online', { data: member });
            }
        });

        // メンバー退出イベント
        this.channel.bind('pusher:member_removed', member => {
            this.logger.log('Member left:', member.info?.name || 'Unknown');
            clearTimeout(this.offlineTimeout); // 念のため既存のタイマーをクリア
            this.offlineTimeout = setTimeout(() => {
                // 遅延後にオフラインイベントをディスパッチ (リロード対策)
                if (window.Livewire) {
                    window.Livewire.dispatch('member-offline', { data: member });
                }
            }, 5000); // 5秒遅延
        });
    }

    /**
     * 接続状態監視イベントを設定
     */
    setupConnectionEvents() {
        if (!this.pusher) return;

        // 接続状態監視（自分の接続状態）
        this.pusher.connection.bind('state_change', states => {
            this.logger.log(`Connection state changed: ${states.previous} -> ${states.current}`);

            if (states.current === 'connected') {
                this.logger.log('Connection restored.');
                this.connectionAttempts = 0; // 成功したためリセット
                if (window.Livewire) {
                    window.Livewire.dispatch('connection-restored');
                }
            } else if (states.current === 'disconnected' || states.current === 'failed') {
                this.logger.log('Connection lost.');
                if (window.Livewire) {
                    window.Livewire.dispatch('connection-lost');
                }
            } else if (states.current === 'connecting') {
                this.logger.log('Connecting...');
            }
        });

        // エラーイベント
        this.pusher.connection.bind('error', error => {
            this.logger.error('Pusher connection error:', error);
            this.handleConnectionError(error);
        });
    }

    /**
     * ハートビートサービスを初期化
     */
    initializeHeartbeat() {
        if (this.debateData) {
            this.heartbeatService = new HeartbeatService({
                contextType: 'debate',
                contextId: this.debateData.debateId,
            });
            // 再接続処理を先にするため30秒後にハートビートを開始
            setTimeout(() => {
                if (this.heartbeatService) {
                    this.heartbeatService.start();
                }
            }, 30000);
        }
    }

    /**
     * ウィンドウイベントを設定
     */
    setupEventListeners() {
        window.addEventListener('online', () => {
            this.logger.log('Browser went online');
            // オンラインになったら接続を再試行
            if (this.pusher && this.pusher.connection.state !== 'connected') {
                this.pusher.connect();
            }
        });

        window.addEventListener('offline', () => {
            this.logger.log('Browser went offline');
        });

        // ページの可視性変更イベント
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.pusher && this.pusher.connection.state !== 'connected') {
                this.logger.log('Page became visible, checking connection');
                this.pusher.connect();
            }
        });
    }

    /**
     * リソースをクリーンアップ
     */
    cleanup() {
        // タイムアウトをクリア
        if (this.connectionTimeout) {
            clearTimeout(this.connectionTimeout);
            this.connectionTimeout = null;
        }

        if (this.offlineTimeout) {
            clearTimeout(this.offlineTimeout);
            this.offlineTimeout = null;
        }

        if (this.heartbeatService) {
            this.heartbeatService.stop();
            this.heartbeatService = null;
        }

        if (this.channel) {
            try {
                this.pusher.unsubscribe(`presence-debate.${this.debateData.debateId}`);
            } catch (error) {
                this.logger.error('Error unsubscribing from channel:', error);
            }
            this.channel = null;
        }

        if (this.pusher) {
            try {
                this.pusher.disconnect();
            } catch (error) {
                this.logger.error('Error disconnecting Pusher:', error);
            }
            this.pusher = null;
        }

        // グローバル参照をクリア
        if (window.pusherInstance === this.pusher) {
            delete window.pusherInstance;
        }

        this.isInitialized = false;
        this.connectionAttempts = 0;
    }
}

export default DebatePresenceManager;
