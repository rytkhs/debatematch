/**
 * DebateMatch Presence Module
 * オンラインステータスと接続管理
 */
import HeartbeatService from '../heartbeat-service';
import Logger from '../logger';

document.addEventListener('DOMContentLoaded', function() {
    const logger = new Logger('Presence');

    // グローバルデータの確認
    if (typeof window.debateData === 'undefined') {
        logger.error('Debate data not available');
        return;
    }

    // Pusher初期化
    const pusher = new Pusher(window.debateData.pusherKey, {
        cluster: window.debateData.pusherCluster,
        authEndpoint: '/pusher/auth',
        encrypted: true
    });

    // プレゼンスチャンネル登録
    const channel = pusher.subscribe(`presence-debate.${window.debateData.debateId}`);

    // ハートビートサービスの初期化と開始
    if (window.debateData) {
        window.heartbeatService = new HeartbeatService({
            contextType: 'debate',
            contextId: window.debateData.debateId
        });
        // 再接続処理を先にするため30秒後にハートビートを開始
        setTimeout(() => {
            window.heartbeatService.start();
        }, 30000);
    }

    let offlineTimeout;
    // オンラインメンバーの初期リスト
    channel.bind('pusher:subscription_succeeded', function(members) {
        // let currentMembersCount = 0;
        members.each(function(member) {
            // currentMembersCount++;
            // リロード対策
            setTimeout(() => {
                Livewire.dispatch('member-online', { data: member });
            }, 300);
        });
        // console.log(`初期メンバー数: ${currentMembersCount}`);
    });

    // メンバー参加イベント
    channel.bind('pusher:member_added', function(member) {
        logger.log('Member joined:', member.info.name);
        clearTimeout(offlineTimeout); // 既存のオフラインタイマーをクリア
        // ユーザーがオンラインになったとき
        Livewire.dispatch('member-online', { data: member });
    });

    // メンバー退出イベント
    channel.bind('pusher:member_removed', function(member) {
        logger.log('Member left:', member.info.name);
        clearTimeout(offlineTimeout); // 念のため既存のタイマーをクリア
        offlineTimeout = setTimeout(() => {
            // 遅延後にオフラインイベントをディスパッチ (リロード対策)
            Livewire.dispatch('member-offline', { data: member });
        }, 5000); // 5秒遅延
    });

    // 接続状態監視（自分の接続状態）
    pusher.connection.bind('state_change', function(states) {
        if (states.current === 'connected') {
            logger.log('Connection restored.');
            Livewire.dispatch('connection-restored');
        } else if (states.current === 'disconnected' || states.current === 'failed') {
            logger.log('Connection lost.');
            Livewire.dispatch('connection-lost');
        } else if (states.current === 'connecting') {
            logger.log('Connecting...');
        }
    });

    // ウィンドウフォーカス時の処理
    // window.addEventListener('focus', function() {
    //     Livewire.dispatch('browser-focused');
    // });

    // ウィンドウブラー時の処理
    // window.addEventListener('blur', function() {
    //     Livewire.dispatch('browser-blurred');
    // });

    // ページ終了時の処理
    // window.addEventListener('beforeunload', function() {
    //     Livewire.dispatch('user-leaving');
    // });

    window.addEventListener('offline', function() {
        logger.log('offline');
    });

    window.addEventListener('online', function() {
        logger.log('online');
    });
});
