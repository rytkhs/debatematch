if (!window.debateData) {
    console.error('window.debateDataが見つかりません');
}

const { debateId } = window.debateData || {};
console.log('debateId:', debateId);

// 通知音を再生する関数（ファイルを指定可能）
function playNotificationSound(audioId = 'messageNotification') {
    const audio = document.getElementById(audioId);
    if (audio) {
        audio.currentTime = 0;
        audio.volume = 0.5;
        audio.play().catch(err => console.error('通知音再生エラー:', err));
    }
}

// 通知音機能の実装 - メッセージ受信時
window.Echo.private(`debate.${debateId}`)
    .listen('DebateMessageSent', () => {
        playNotificationSound('messageNotification');
    });

// ターン変更時には別の通知音を鳴らす
window.Echo.private(`debate.${debateId}`)
    .listen('TurnAdvanced', () => {
        playNotificationSound('turnAdvancedNotification');
    });
