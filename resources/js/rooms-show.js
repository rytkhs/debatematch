import HeartbeatService from './heartbeat-service';

// ルームページの機能強化
class RoomManager {
    constructor(options) {
        this.roomId = options.roomId;
        this.userId = options.authUserId;
        this.pusherKey = options.pusherKey;
        this.pusherCluster = options.pusherCluster;

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

        console.log('チャンネル初期化:', this.channel);

        // イベントハンドラの登録
        this.registerEventHandlers();

    }

    registerEventHandlers() {
        // ユーザー参加イベント
        this.channel.bind('App\\Events\\UserJoinedRoom', data => {
            if (data.user.id !== this.userId) {
                this.showNotification(`${data.user.name} さんが参加しました`, 'info');
            }
            console.log(`${data.user.name} さんが参加しました`);
        });

        // ユーザー退出イベント
        this.channel.bind('App\\Events\\UserLeftRoom', data => {
            if (data.user.id !== this.userId) {
                this.showNotification(`${data.user.name} さんが退出しました`, 'warning');
            }
            console.log(`${data.user.name} さんが退出しました`);
        });

        // クリエイター退出イベント
        this.channel.bind('App\\Events\\CreatorLeftRoom', data => {
            if (data.creator.id === this.userId) return;
            alert("ホストが退出したため、ルームは閉鎖されました");

            // this.showNotification("ホストが退出したため、ルームは閉鎖されました", 'error');
            window.location.href = '/';
        });

        // ディベート開始イベント
        this.channel.bind('App\\Events\\DebateStarted', data => {
            console.log('ディベート開始イベントを受信しました');
            console.log('ディベートID:', data.debateId);

            this.showNotification('ディベートを開始します。ページ移動の準備をしています...', 'success');
            this.showLoadingCountdown();

            const debateUrl = `/debate/${data.debateId}`;

            // カウントダウン設定
            let countdown = 5;
            const countdownElement = document.querySelector('#countdown-overlay .text-gray-500');

            if (countdownElement) {
                countdownElement.innerHTML = `${countdown}秒後にディベートページへ移動します...`;
            }

            // カウントダウンタイマーで確実にリダイレクト
            const countdownTimer = setInterval(() => {
                countdown--;

                if (countdownElement) {
                    countdownElement.innerHTML = `${countdown}秒後にディベートページへ移動します...`;
                }

                // カウントダウン終了時の処理
                if (countdown <= 0) {
                    clearInterval(countdownTimer);
                    window.location.href = debateUrl;
                }
            }, 1000);
        });

        let offlineTimeout;
            // オンラインメンバーの初期リスト
            this.presenceChannel.bind('pusher:subscription_succeeded', function(members) {
            members.each(function(member) {
                Livewire.dispatch('member-online', { data: member });
            });
        });

        // プレゼンスチャンネルのメンバー状態変更イベント
        this.presenceChannel.bind('pusher:member_removed', member => {
            console.log(member.info.name + ' さんが切断されました');
            clearTimeout(offlineTimeout);
            offlineTimeout = setTimeout(() => {
                // 遅延後にオフラインイベントをディスパッチ (リロード対策)
                Livewire.dispatch('member-offline', { data: member });
            }, 5000);
        });

        this.presenceChannel.bind('pusher:member_added', member => {
            console.log(member.info.name + ' さんが再接続しました');
            clearTimeout(offlineTimeout);

            Livewire.dispatch('member-online', { data: member });
        });

        // 接続状態の監視
        this.pusher.connection.bind('state_change', states => {
            if (states.current === 'disconnected' || states.current === 'failed') {
                this.disconnectionHandler.handleSelfDisconnection();
            } else if (states.current === 'connected' && states.previous === 'disconnected') {
                this.disconnectionHandler.hideAlert();
                this.disconnectionHandler.stopCountdown();
                this.showNotification('接続が回復しました', 'success');
            }
        });
    }

    // 通知を表示する関数
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

    // カウントダウンオーバーレイの表示
    showLoadingCountdown() {
        const overlay = document.getElementById('countdown-overlay');
        if (overlay) {
            overlay.classList.remove('hidden');
        }
    }
}

// DOMロード時に初期化
document.addEventListener('DOMContentLoaded', () => {

    const roomManager = new RoomManager(window.roomData);

    window.roomManager = roomManager;

    // HeartbeatService の初期化と開始
    if (window.roomData) {
        window.heartbeatService = new HeartbeatService({
            contextType: 'room',
            contextId: window.roomData.roomId
        });
        // 再接続処理を先にするため30秒後にハートビートを開始
        setTimeout(() => {
            window.heartbeatService.start();
        }, 30000);
    }
});
