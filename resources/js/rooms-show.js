// Pusherの初期化
var pusher = new Pusher(window.roomData.pusherKey, {
    cluster: window.roomData.pusherCluster,
    authEndpoint: '/pusher/auth',
    encrypted: true
});

// チャンネルの購読
var channel = pusher.subscribe('rooms.' + window.roomData.roomId);
var presenceChannel = pusher.subscribe('presence-room.' + window.roomData.roomId);

// ユーザーイベントを処理するヘルパー関数
const handleUserEvent = (eventName, action) => data => {
    if (data.user.id !== window.roomData.authUserId) {
        alert(`${data.user.name} さんが${eventName}しました。`);
    }
    console.log(`${data.user.name} さんが${eventName}しました。`);
};

// Pusherイベントの登録
const registerPusherEvents = () => {
    // ルーム参加・退出イベントのバインド
    channel.bind('App\\Events\\UserJoinedRoom', handleUserEvent('参加'));
    channel.bind('App\\Events\\UserLeftRoom', handleUserEvent('退出'));

    // プレゼンスチャンネルのメンバー状態変更イベントのバインド
    presenceChannel.bind('pusher:member_removed', member => {
        console.log(member.info.user_info.name + ' さんが切断されました。');
    });

    presenceChannel.bind('pusher:member_added', member => {
        console.log(member.info.user_info.name + ' さんが再接続しました。');
    });
};

registerPusherEvents();

// ページ遷移を制御するハンドラー
const navigationHandler = {
    isLegitimateNavigation: false,

    // 正当なナビゲーションを許可
    allowNavigation() {
        this.isLegitimateNavigation = true;
    },

    // イベントリスナーの初期化
    initEventListeners() {
        // リンクやフォームクリック時のナビゲーション許可
        document.addEventListener('click', e => {
            if (e.target.closest('a, form')) this.allowNavigation();
        });

        // ページ離脱時の確認
        window.addEventListener('beforeunload', e => {
            if (!this.isLegitimateNavigation) {
                e.preventDefault();
                return e.returnValue = '';
            }
        });
    }
};

navigationHandler.initEventListeners();

// カウントダウンオーバーレイのHTML要素を作成
const createCountdownOverlay = () => {
    const overlay = document.createElement('div');
    overlay.id = 'countdown-overlay';
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden';
    overlay.innerHTML = `
        <div class="bg-white rounded-lg p-8 text-center flex flex-col items-center space-y-4">
            <h2 class="text-2xl font-bold text-gray-800">ディベートを開始します</h2>
            <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
        </div>
    `;
    return overlay;
};

document.addEventListener('DOMContentLoaded', () => {
    // カウントダウンオーバーレイを追加
    document.body.appendChild(createCountdownOverlay());

    // ディベート開始イベントのリスナー設定
    const debateChannel = window.Echo.private(`debate.${window.roomData.roomId}`);
    debateChannel.listen('DebateStarted', data => {
        window.showLoadingCountdown();
        setTimeout(() => window.location.assign(data.redirect_url), 5000);
    });
});
