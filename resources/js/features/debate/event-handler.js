import Logger from '../../services/logger.js';
import DOMUtils from '../../utils/dom-utils.js';
import { showNotification } from '../../services/notification.js';

/**
 * ディベートイベントハンドラー
 * WebSocketイベントとプレゼンス管理を担当
 */
class DebateEventHandler {
    constructor(debateId) {
        this.logger = new Logger('DebateEventHandler');
        this.debateId = debateId;
        this.presenceChannel = null;
        this.offlineTimeout = null;
        this.pendingInitialMembers = null;
        this.isInitialized = false;
        // presenceチャンネルの初期化はLivewireコンポーネントの準備完了後に実行
        this.delayedInitialize();
    }

    /**
     * 遅延初期化 - Pusher接続とLivewireコンポーネントの準備完了まで待機
     */
    delayedInitialize() {
        // 両方の条件が満たされるまで待機
        this.waitForInitializationConditions();
    }

    /**
     * 初期化条件を満たすまで待機
     */
    waitForInitializationConditions() {
        const checkConditions = () => {
            const pusherReady = this.isPusherReady();
            const livewireReady = window.Livewire && this.areLivewireComponentsReady();

            this.logger.log('初期化条件チェック:', { pusherReady, livewireReady });

            if (pusherReady && livewireReady) {
                this.logger.log(
                    'すべての初期化条件が満たされました。presenceチャンネルを初期化します。'
                );
                this.initEchoListeners();
                return true;
            }
            return false;
        };

        // 即座にチェック
        if (checkConditions()) {
            return;
        }

        // Livewireコンポーネントの準備完了イベントを監視
        document.addEventListener('livewire:components-ready', () => {
            if (!this.isInitialized) {
                this.logger.log('Livewireコンポーネントの準備完了イベントを受信しました。');
                setTimeout(() => checkConditions(), 100);
            }
        });

        // 定期的にチェック
        const checkInterval = setInterval(() => {
            if (checkConditions()) {
                clearInterval(checkInterval);
            }
        }, 200);

        // 最大15秒でタイムアウト（より長めに設定）
        setTimeout(() => {
            clearInterval(checkInterval);
            if (!this.isInitialized) {
                this.logger.warn(
                    '初期化条件の待機中にタイムアウトしました。強制的に初期化します。'
                );
                this.initEchoListeners();
            }
        }, 15000);
    }

    /**
     * Pusher接続の準備状況を確認
     */
    isPusherReady() {
        if (!window.Echo) {
            return false;
        }

        // EchoのPusherインスタンスの接続状態を確認
        const pusher = window.Echo.connector.pusher;
        if (!pusher) {
            return false;
        }

        // 接続状態を確認（connected または connecting でも可）
        const state = pusher.connection.state;
        const isReady = state === 'connected' || state === 'connecting';

        this.logger.log('Pusher接続状態:', state, 'Ready:', isReady);
        return isReady;
    }

    /**
     * Livewireコンポーネントの準備状況を確認
     */
    areLivewireComponentsReady() {
        // Participants コンポーネントの存在確認
        const participantsElement = document.querySelector('[wire\\:id]');
        if (!participantsElement) {
            return false;
        }

        const componentId = participantsElement.getAttribute('wire:id');
        const component = window.Livewire.find(componentId);
        return component !== null;
    }

    /**
     * Echo リスナーを初期化
     */
    initEchoListeners() {
        if (this.isInitialized) {
            return;
        }

        if (!window.Echo || !this.debateId) {
            this.logger.error('Echo または debateId が設定されていません');
            return;
        }

        this.presenceChannel = window.Echo.join(`debate.${this.debateId}`)
            .here(users => {
                this.logger.log('Initial members:', users);
                this.handleInitialMembers(users);
            })
            .joining(user => {
                this.logger.log(`${user.name} さんが再接続しました`);
                clearTimeout(this.offlineTimeout);
                this.dispatchMemberOnline(user);
            })
            .leaving(user => {
                this.logger.log(`${user.name} さんが切断されました`);
                clearTimeout(this.offlineTimeout);
                this.offlineTimeout = setTimeout(() => {
                    this.dispatchMemberOffline(user);
                }, 3000);
            })
            .listen('DebateFinished', e => this.handleDebateFinished(e))
            .listen('DebateEvaluated', e => this.handleDebateEvaluated(e))
            .listen('DebateTerminated', e => this.handleDebateTerminated(e))
            .listen('EarlyTerminationExpired', e => this.handleEarlyTerminationExpired(e));

        this.isInitialized = true;
    }

    /**
     * 初期メンバーを処理
     */
    handleInitialMembers(users) {
        if (this.areLivewireComponentsReady()) {
            users.forEach(user => this.dispatchMemberOnline(user));
        } else {
            // Livewireコンポーネントが準備されていない場合は一時保存
            this.pendingInitialMembers = users;
            this.waitForLivewireAndProcessInitialMembers();
        }
    }

    /**
     * Livewireコンポーネントの準備完了を待って初期メンバーを処理
     */
    waitForLivewireAndProcessInitialMembers() {
        const checkInterval = setInterval(() => {
            if (this.areLivewireComponentsReady()) {
                clearInterval(checkInterval);
                if (this.pendingInitialMembers) {
                    this.pendingInitialMembers.forEach(user => this.dispatchMemberOnline(user));
                    this.pendingInitialMembers = null;
                }
            }
        }, 100);

        // 最大5秒でタイムアウト
        setTimeout(() => {
            clearInterval(checkInterval);
            if (this.pendingInitialMembers) {
                this.logger.warn(
                    'Livewireコンポーネントの準備完了を待機中にタイムアウトしました。初期メンバーを破棄します。'
                );
                this.pendingInitialMembers = null;
            }
        }, 5000);
    }

    /**
     * member-onlineイベントを確実に送信
     */
    dispatchMemberOnline(user) {
        if (window.Livewire) {
            Livewire.dispatch('member-online', { data: user });
        }
    }

    /**
     * member-offlineイベントを確実に送信
     */
    dispatchMemberOffline(user) {
        if (window.Livewire) {
            Livewire.dispatch('member-offline', { data: user });
        }
    }

    handleDebateFinished() {
        showNotification({
            title: window.translations?.debate_finished_title || 'ディベートが終了しました',
            message:
                window.translations?.evaluating_message ||
                'AIによる評価を行っています。しばらくお待ちください...',
            type: 'info',
            duration: 10000,
        });
        this.showFinishedOverlay();
    }

    handleDebateEvaluated() {
        showNotification({
            title: window.translations?.evaluation_complete_title || 'ディベート評価が完了しました',
            message: window.translations?.redirecting_to_results || '結果ページへ移動します',
            type: 'success',
            duration: 2000,
        });
        this.logger.log('Debate evaluated:', event.debateId);
        setTimeout(() => {
            window.location.href = `/debate/${this.debateId}/result`;
        }, 2000);
    }

    handleDebateTerminated() {
        setTimeout(() => {
            alert(
                window.translations?.host_left_terminated ||
                    '相手との接続が切断されたため、ディベートを終了します'
            );
            window.location.href = '/';
        }, 2000);
    }

    handleEarlyTerminationExpired() {
        showNotification({
            title:
                window.translations?.early_termination_expired_notification ||
                '早期終了提案がタイムアウトしました',
            message:
                window.translations?.early_termination_timeout_message ||
                '早期終了の提案は1分で期限切れになりました。ディベートを継続します。',
            type: 'warning',
            duration: 8000,
        });
    }

    showFinishedOverlay() {
        if (DOMUtils.safeGetElement('debate-finished-overlay', false, 'DebateEventHandler')) return;
        const overlay = document.createElement('div');
        overlay.id = 'debate-finished-overlay';
        overlay.className =
            'fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50';
        overlay.innerHTML = `
            <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full mx-4 border-t-4 border-primary">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary bg-opacity-10 mb-4">
                        <span class="material-icons text-primary text-5xl">emoji_events</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2 tracking-tight">${window.translations?.debate_finished_overlay_title || 'ディベート終了'}</h2>
                    <p class="text-gray-600 mb-6 leading-relaxed">${window.translations?.evaluating_overlay_message || 'ディベートが終了しました。現在、AIが評価を行っています...'}</p>
                    <div class="flex items-center justify-center space-x-3 mb-8">
                        <div class="w-3 h-3 bg-primary rounded-full animate-pulse" style="animation-delay: 0s"></div>
                        <div class="w-4 h-4 bg-primary rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
                        <div class="w-3 h-3 bg-primary rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    /**
     * リソースをクリーンアップ
     */
    cleanup() {
        if (this.presenceChannel) {
            window.Echo.leave(`debate.${this.debateId}`);
            this.presenceChannel = null;
        }

        if (this.offlineTimeout) {
            clearTimeout(this.offlineTimeout);
        }
        this.logger.log('Debate event handler cleaned up.');
    }
}

export default DebateEventHandler;
