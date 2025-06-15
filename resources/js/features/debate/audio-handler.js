import Logger from '../../services/logger.js';

/**
 * オーディオハンドラー
 * 通知音の再生機能を管理
 */
class AudioHandler {
    constructor(debateData) {
        this.logger = new Logger('AudioHandler');
        this.debateData = debateData;
        this.userInteracted = false;
    }

    /**
     * オーディオハンドラーを初期化
     */
    initialize() {
        if (!this.debateData) {
            this.logger.error('window.debateDataが見つかりません');
            return;
        }

        const { debateId } = this.debateData;
        this.logger.log('debateId:', debateId);

        this.setupUserInteractionListeners();
        this.setupWebSocketListeners(debateId);
    }

    /**
     * ユーザーインタラクションリスナーを設定
     */
    setupUserInteractionListeners() {
        // ユーザーインタラクションイベントをリッスン
        ['click', 'touchstart', 'keydown'].forEach(eventType => {
            document.addEventListener(eventType, () => this.activateAudio(), { once: false });
        });
    }

    /**
     * WebSocketリスナーを設定
     */
    setupWebSocketListeners(debateId) {
        if (!window.Echo) {
            this.logger.error('Echo not available');
            return;
        }

        // 通知音機能の実装 - メッセージ受信時
        window.Echo.private(`debate.${debateId}`).listen('DebateMessageSent', () => {
            if (this.userInteracted) {
                this.playNotificationSound('messageNotification');
            }
        });

        // ターン変更時には別の通知音を鳴らす
        window.Echo.private(`debate.${debateId}`).listen('TurnAdvanced', () => {
            if (this.userInteracted) {
                this.playNotificationSound('turnAdvancedNotification');
            }
        });
    }

    /**
     * 通知音を再生する関数
     */
    playNotificationSound(audioId = 'messageNotification') {
        const audio = document.getElementById(audioId);
        if (audio) {
            audio.currentTime = 0;
            audio.volume = 0.5;
            audio.play().catch(err => this.logger.error('通知音再生エラー:', err));
        }
    }

    /**
     * ユーザーのインタラクションを検知して音声再生を有効化
     */
    activateAudio() {
        if (!this.userInteracted) {
            this.userInteracted = true;

            // 無音を再生してオーディオコンテキストをアクティブ化
            const silentAudio = document.getElementById('messageNotification');
            if (silentAudio) {
                silentAudio.volume = 0.01;
                silentAudio
                    .play()
                    .then(() => {
                        silentAudio.pause();
                        silentAudio.currentTime = 0;
                        this.logger.log('オーディオコンテキストがアクティブ化されました');
                    })
                    .catch(e => this.logger.log('オーディオのアクティブ化に失敗:', e));
            }
        }
    }
}

export default AudioHandler;
