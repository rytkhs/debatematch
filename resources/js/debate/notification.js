if (!window.debateData) {
    console.error('window.debateDataが見つかりません');
}

const { debateId } = window.debateData || {};
console.log('debateId:', debateId);

// 通知音を再生する関数
function playNotificationSound(audioId = 'messageNotification') {
    const audio = document.getElementById(audioId);
    if (audio) {
        audio.currentTime = 0;
        audio.volume = 0.5;
        audio.play().catch(err => console.error('通知音再生エラー:', err));
    }
}

// ユーザーのインタラクションを検知して音声再生を有効化
let userInteracted = false;
const activateAudio = () => {
    if (!userInteracted) {
        userInteracted = true;

        // 無音を再生してオーディオコンテキストをアクティブ化
        const silentAudio = document.getElementById('messageNotification');
        if (silentAudio) {
            silentAudio.volume = 0.01;
            silentAudio.play().then(() => {
                silentAudio.pause();
                silentAudio.currentTime = 0;
                console.log('オーディオコンテキストがアクティブ化されました');
            }).catch(e => console.log('オーディオのアクティブ化に失敗:', e));
        }
    }
};

// ユーザーインタラクションイベントをリッスン
['click', 'touchstart', 'keydown'].forEach(eventType => {
    document.addEventListener(eventType, activateAudio, { once: false });
});

// 通知音機能の実装 - メッセージ受信時
window.Echo.private(`debate.${debateId}`)
    .listen('DebateMessageSent', () => {
        if (userInteracted) {
            playNotificationSound('messageNotification');
        }
    });

// ターン変更時には別の通知音を鳴らす
window.Echo.private(`debate.${debateId}`)
    .listen('TurnAdvanced', () => {
        if (userInteracted) {
            playNotificationSound('turnAdvancedNotification');
        }
    });
