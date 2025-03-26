 /**
 * DebateMatch Presence Module
 * オンラインステータスと接続管理
 */
document.addEventListener('DOMContentLoaded', function() {
    // グローバルデータの確認
    if (typeof window.debateData === 'undefined') {
        console.error('Debate data not available');
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

    let offlineTimeout;
    // オンラインメンバーの初期リスト
    channel.bind('pusher:subscription_succeeded', function(members) {
        members.each(function(member) {
            Livewire.dispatch('member-online', { data: member });
        });
    });

    // メンバー参加イベント
    channel.bind('pusher:member_added', function(member) {
        console.log('Member joined:', member.info.name);
        clearTimeout(offlineTimeout); // 既存のオフラインタイマーをクリア
        // ユーザーがオンラインになったとき
        Livewire.dispatch('member-online', { data: member });
    });

    // メンバー退出イベント
    channel.bind('pusher:member_removed', function(member) {
        console.log('Member left:', member.info.name);
        clearTimeout(offlineTimeout); // 念のため既存のタイマーをクリア
        offlineTimeout = setTimeout(() => {
            // 遅延後にオフラインイベントをディスパッチ (リロード対策)
            Livewire.dispatch('member-offline', { data: member });
        }, 5000); // 5秒遅延
    });

    // 接続状態監視（自分の接続状態）
    pusher.connection.bind('state_change', function(states) {
        if (states.current === 'connected') {
            console.log('Connection restored.');
            Livewire.dispatch('connection-restored');
        } else if (states.current === 'disconnected' || states.current === 'failed') {
            console.log('Connection lost.');
            Livewire.dispatch('connection-lost');
        } else if (states.current === 'connecting') {
            console.log('Connecting...');
        }
    });

    // ウィンドウフォーカス時の処理
    window.addEventListener('focus', function() {
        // ブラウザタブがアクティブになった時にオンライン状態を通知
        // console.log('DebateMatch Presence Module: Browser focused.');
        Livewire.dispatch('browser-focused');
    });

    // ウィンドウブラー時の処理
    window.addEventListener('blur', function() {
        // ブラウザタブが非アクティブになった時に通知
        // console.log('DebateMatch Presence Module: Browser blurred.');
        Livewire.dispatch('browser-blurred');
    });

    // ページ終了時の処理
    window.addEventListener('beforeunload', function() {
        // 可能ならページ離脱時にクリーンアップ
        // console.log('DebateMatch Presence Module: User leaving.');
        Livewire.dispatch('user-leaving');
    });

    window.addEventListener('offline', function() {
        // オフライン状態時の処理を手動で実行
        // document.querySelectorAll('[wire:offline]').forEach(el => {
        //   el.style.display = 'block';
        // });
        console.log('offline');
      });

    window.addEventListener('online', function() {
        console.log('online');
    });
});
