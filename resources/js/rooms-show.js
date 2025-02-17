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
