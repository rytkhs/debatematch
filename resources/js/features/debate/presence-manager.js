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
    }

    /**
     * プレゼンス管理を初期化
     */
    initialize() {
        // グローバルデータの確認
        if (!this.debateData) {
            this.logger.error('Debate data not available');
            return;
        }

        this.initializePusher();
        this.initializeHeartbeat();
        this.setupEventListeners();
    }

    /**
     * Pusherを初期化
     */
    initializePusher() {
        // Pusher初期化
        this.pusher = new Pusher(this.debateData.pusherKey, {
            cluster: this.debateData.pusherCluster,
            authEndpoint: '/pusher/auth',
            encrypted: true
        });

        // プレゼンスチャンネル登録
        this.channel = this.pusher.subscribe(`presence-debate.${this.debateData.debateId}`);

        this.setupPresenceEvents();
        this.setupConnectionEvents();
    }

    /**
     * プレゼンスイベントを設定
     */
    setupPresenceEvents() {
        // オンラインメンバーの初期リスト
        this.channel.bind('pusher:subscription_succeeded', (members) => {
            members.each((member) => {
                // リロード対策
                setTimeout(() => {
                    if (window.Livewire) {
                        window.Livewire.dispatch('member-online', { data: member });
                    }
                }, 300);
            });
        });

        // メンバー参加イベント
        this.channel.bind('pusher:member_added', (member) => {
            this.logger.log('Member joined:', member.info.name);
            clearTimeout(this.offlineTimeout); // 既存のオフラインタイマーをクリア
            // ユーザーがオンラインになったとき
            if (window.Livewire) {
                window.Livewire.dispatch('member-online', { data: member });
            }
        });

        // メンバー退出イベント
        this.channel.bind('pusher:member_removed', (member) => {
            this.logger.log('Member left:', member.info.name);
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
        // 接続状態監視（自分の接続状態）
        this.pusher.connection.bind('state_change', (states) => {
            if (states.current === 'connected') {
                this.logger.log('Connection restored.');
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
    }

    /**
     * ハートビートサービスを初期化
     */
    initializeHeartbeat() {
        if (this.debateData) {
            this.heartbeatService = new HeartbeatService({
                contextType: 'debate',
                contextId: this.debateData.debateId
            });
            // 再接続処理を先にするため30秒後にハートビートを開始
            setTimeout(() => {
                this.heartbeatService.start();
            }, 30000);
        }
    }

    /**
     * ウィンドウイベントを設定
     */
    setupEventListeners() {
        window.addEventListener('offline', () => {
            this.logger.log('offline');
        });

        window.addEventListener('online', () => {
            this.logger.log('online');
        });
    }

    /**
     * リソースをクリーンアップ
     */
    cleanup() {
        if (this.offlineTimeout) {
            clearTimeout(this.offlineTimeout);
        }

        if (this.heartbeatService) {
            this.heartbeatService.stop();
        }

        if (this.channel) {
            this.pusher.unsubscribe(`presence-debate.${this.debateData.debateId}`);
        }

        if (this.pusher) {
            this.pusher.disconnect();
        }
    }
}

export default DebatePresenceManager;
