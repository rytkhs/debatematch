import Logger from '../../services/logger.js';
import { showNotification } from '../../services/notification.js';

/**
 * ルーム待機ページでのWebSocketイベントを処理するクラス
 */
export class RoomEventHandler {
    constructor(roomId, userId) {
        this.logger = new Logger('RoomEventHandler');
        this.roomId = roomId;
        this.userId = userId;
        this.channelName = `room.${this.roomId}`;
        this.presenceChannelName = `room.${this.roomId}`;
        this.channel = null;
        this.presenceChannel = null;
        this.offlineTimeout = null;

        this.initEchoListeners();
    }

    initEchoListeners() {
        if (!window.Echo || !this.roomId) {
            this.logger.error('Echo または roomId が設定されていません');
            return;
        }

        // 変更点: 安全なプライベートチャンネルを使用
        this.channel = window.Echo.private(this.channelName)
            .listen('UserJoinedRoom', data => this.handleUserJoined(data))
            .listen('UserLeftRoom', data => this.handleUserLeft(data))
            .listen('CreatorLeftRoom', data => this.handleCreatorLeft(data))
            .listen('DebateStarted', data => this.handleDebateStarted(data));

        this.presenceChannel = window.Echo.join(this.presenceChannelName)
            .here(users => {
                users.forEach(user => Livewire.dispatch('member-online', { data: user }));
            })
            .joining(user => {
                this.logger.log(`${user.name} さんが再接続しました`);
                clearTimeout(this.offlineTimeout);
                Livewire.dispatch('member-online', { data: user });
            })
            .leaving(user => {
                this.logger.log(`${user.name} さんが切断されました`);
                clearTimeout(this.offlineTimeout);
                this.offlineTimeout = setTimeout(() => {
                    Livewire.dispatch('member-offline', { data: user });
                }, 5000);
            });
    }

    handleUserJoined(data) {
        if (data.user.id !== this.userId) {
            const title = window.translations?.user_joined_room_title || 'User Joined';
            const message = (
                window.translations?.rooms?.user_joined_room || ':name has joined.'
            ).replace(':name', data.user.name);
            showNotification({ title, message, type: 'info' });
        }
        this.logger.log(`${data.user.name} さんが参加しました`);
    }

    handleUserLeft(data) {
        if (data.user.id !== this.userId) {
            const title = window.translations?.user_left_room_title || 'User Left';
            const message = (
                window.translations?.rooms?.user_left_room || ':name has left.'
            ).replace(':name', data.user.name);
            showNotification({ title, message, type: 'warning' });
        }
        this.logger.log(`${data.user.name} さんが退出しました`);
    }

    handleCreatorLeft(data) {
        if (data.creator.id === this.userId) return;
        setTimeout(() => {
            alert(
                window.translations?.rooms?.host_left_room_closed ||
                    'The host has left, so the room has been closed.'
            );
            window.location.href = '/';
        }, 2000);
    }

    handleDebateStarted(data) {
        this.logger.log('ディベート開始イベントを受信しました:', data.debateId);
        showNotification({
            title: window.translations?.debate_starting_title || 'Debate Starting',
            message:
                window.translations?.rooms?.debate_starting_message ||
                'Starting the debate. Preparing to navigate...',
            type: 'success',
        });
        this.showLoadingCountdown();
        this.startCountdownRedirect(`/debate/${data.debateId}`);
    }

    cleanup() {
        // 変更点: 正しいクリーンアップ方法
        if (this.channel) {
            window.Echo.leave(this.channelName);
            this.channel = null;
        }
        if (this.presenceChannel) {
            window.Echo.leave(this.presenceChannelName);
            this.presenceChannel = null;
        }

        if (this.offlineTimeout) {
            clearTimeout(this.offlineTimeout);
        }
        this.logger.log('Room event listeners cleaned up.');
    }

    startCountdownRedirect(debateUrl) {
        let countdown = 5;
        const countdownElement = document.querySelector('#countdown-overlay .text-gray-500');
        if (countdownElement) {
            countdownElement.innerHTML = (
                window.translations?.rooms?.redirecting_in_seconds ||
                'Redirecting to the debate page in :seconds seconds...'
            ).replace(':seconds', countdown);
        }
        const countdownTimer = setInterval(() => {
            countdown--;
            if (countdownElement) {
                countdownElement.innerHTML = (
                    window.translations?.rooms?.redirecting_in_seconds ||
                    'Redirecting to the debate page in :seconds seconds...'
                ).replace(':seconds', countdown);
            }
            if (countdown <= 0) {
                clearInterval(countdownTimer);
                window.location.href = debateUrl;
            }
        }, 1000);
    }

    showLoadingCountdown() {
        const overlay = document.getElementById('countdown-overlay');
        if (overlay) {
            overlay.classList.remove('hidden');
        }
    }
}
