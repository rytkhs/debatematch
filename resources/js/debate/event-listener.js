import Logger from '../logger';

/**
 * ディベート評価通知用モジュール
 *
 * このモジュールは主にWebSocketイベント（ディベート終了、評価完了など）の通知を担当します。
 */
class EventListener {
    constructor(debateId) {
        this.logger = new Logger('EventListener');
        this.debateId = debateId;
        this.channel = null;
        this.initEchoListeners();
    }

    /**
     * Echo リスナーを初期化
     */
    initEchoListeners() {
        if (!window.Echo || !this.debateId) {
            this.logger.error('Echo または debateId が設定されていません');
            return;
        }

        // ディベート終了イベントを監視
        this.channel = window.Echo.private(`debate.${this.debateId}`)
            .listen('DebateFinished', (e) => {
                this.handleDebateFinished(e);
            })
            .listen('DebateEvaluated', (e) => {
                this.handleDebateEvaluated(e);
            })
            .listen('DebateTerminated', (e) => {
                this.logger.log('DebateTerminated イベントを受信:', e);
                this.handleDebateTerminated(e);
            })
            .listen('EarlyTerminationExpired', (e) => {
                this.logger.log('EarlyTerminationExpired イベントを受信:', e);
                this.handleEarlyTerminationExpired(e);
            });
    }

    /**
     * ディベート終了イベントの処理
     */
    handleDebateFinished(event) {
        // 終了通知を表示
        this.showNotification({
            title: window.translations?.debate_finished_title || 'ディベートが終了しました',
            message: window.translations?.evaluating_message || 'AIによる評価を行っています。しばらくお待ちください...',
            type: 'info',
            duration: 10000
        });

        // オーバーレイを表示
        this.showFinishedOverlay();
    }

    /**
     * ディベート評価完了イベントの処理
     */
    handleDebateEvaluated(event) {
        // 完了通知を表示
        this.showNotification({
            title: window.translations?.evaluation_complete_title || 'ディベート評価が完了しました',
            message: window.translations?.redirecting_to_results || '結果ページへ移動します',
            type: 'success',
            duration: 2000
        });
        this.logger.log(event.debateId);

        // 評価結果ページへのURLを生成
        const resultUrl = `/debate/${this.debateId}/result`;

        // 2秒後に評価結果ページへリダイレクト
        setTimeout(() => {
            window.location.href = resultUrl;
        }, 2000);
    }

    /**
     * ディベート強制終了イベントの処理
     */
    handleDebateTerminated(event) {
        // 終了通知を表示
        alert(window.translations?.host_left_terminated || '相手との接続が切断されたため、ディベートを終了します');
        window.location.href = '/';
    }

    /**
     * 早期終了提案タイムアウトイベントの処理
     */
    handleEarlyTerminationExpired(event) {
        this.showNotification({
            title: window.translations?.early_termination_expired_notification || '早期終了提案がタイムアウトしました',
            message: window.translations?.early_termination_timeout_message || '早期終了の提案は1分で期限切れになりました。ディベートを継続します。',
            type: 'warning',
            duration: 8000
        });
    }

    /**
     * 通知を表示
     */
    showNotification(options) {
        // 既存の通知コンポーネントがあればそれを使用
        if (window.showNotification) {
            window.showNotification(options);
            return;
        }

        // シンプルな通知表示（フォールバック）
        const notificationElement = document.createElement('div');
        // TailwindCSSクラスとアニメーション用のスタイルを設定
        notificationElement.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 border-l-4`;

        // タイプに応じた背景色とテキスト色を設定
        let bgColorClass, borderColorClass, textColorClass, iconColorClass, iconName;
        switch (options.type) {
            case 'success':
                bgColorClass = 'bg-green-50';
                borderColorClass = 'border-green-500';
                textColorClass = 'text-green-800';
                iconColorClass = 'text-green-600';
                iconName = 'check_circle';
                break;
            case 'error':
                bgColorClass = 'bg-red-50';
                borderColorClass = 'border-red-500';
                textColorClass = 'text-red-800';
                iconColorClass = 'text-red-600';
                iconName = 'error';
                break;
            case 'warning':
                bgColorClass = 'bg-yellow-50';
                borderColorClass = 'border-yellow-500';
                textColorClass = 'text-yellow-800';
                iconColorClass = 'text-yellow-600';
                iconName = 'warning';
                break;
            default: // info
                bgColorClass = 'bg-blue-50';
                borderColorClass = 'border-blue-500';
                textColorClass = 'text-blue-800';
                iconColorClass = 'text-blue-600';
                iconName = 'info';
                break;
        }

        notificationElement.classList.add(bgColorClass, borderColorClass);

        notificationElement.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    <span class="material-icons ${iconColorClass}">
                        ${iconName}
                    </span>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium ${textColorClass}">${options.title}</h3>
                    <div class="mt-1 text-sm ${textColorClass.replace('-800', '-700')}">${options.message}</div>
                </div>
            </div>
        `;

        // アニメーションの初期状態を設定
        notificationElement.style.transition = 'all 0.5s ease-in-out';
        notificationElement.style.transform = 'translateX(100%)';
        notificationElement.style.opacity = '0';

        document.body.appendChild(notificationElement);

        // 強制的にレイアウト計算を行う
        notificationElement.getBoundingClientRect();

        // アニメーションを開始
        setTimeout(() => {
            notificationElement.style.transform = 'translateX(0)';
            notificationElement.style.opacity = '1';
        }, 10);

        const displayDuration = options.duration || 5000;
        const transitionDuration = 500;

        setTimeout(() => {
            notificationElement.style.transform = 'translateX(100%)';
            notificationElement.style.opacity = '0';

            // アニメーション終了後に要素を削除
            setTimeout(() => {
                notificationElement.remove();
            }, transitionDuration);

        }, displayDuration);
    }

    /**
     * 終了オーバーレイを表示
     */
    showFinishedOverlay() {
        // すでにオーバーレイがあれば何もしない
        if (document.getElementById('debate-finished-overlay')) {
            return;
        }

        const overlay = document.createElement('div');
        overlay.id = 'debate-finished-overlay';
        overlay.className = 'fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50';

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
                    <a id="result-page-link" href="${window.location.origin}/debate/${this.debateId}/result"
                       class="hidden inline-flex items-center justify-center bg-primary text-white font-bold py-3 px-8 rounded-lg transition duration-300 transform hover:scale-105 hover:shadow-lg">
                        <span class="material-icons mr-2">analytics</span>
                        ${window.translations?.go_to_results_page || '結果ページへ'}
                    </a>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        // 3分後に結果ページへのリンクを表示
        setTimeout(() => {
            const resultLink = document.getElementById('result-page-link');
            if (resultLink) {
                resultLink.classList.remove('hidden');
            }
        }, 180000);

    }


    /**
     * リソースをクリーンアップ
     */
    cleanup() {
        if (this.channel) {
            this.channel.stopListening('DebateFinished');
            this.channel.stopListening('DebateEvaluated');
            this.channel.stopListening('DebateTerminated');
            this.channel.stopListening('EarlyTerminationExpired');
        }
    }
}

// グローバルに公開
window.EventListener = EventListener;

// DOMコンテンツ読み込み完了時に初期化
document.addEventListener('DOMContentLoaded', () => {
    // debateDataがある場合のみ初期化
    if (window.debateData && window.debateData.debateId) {
        window.eventListener = new EventListener(window.debateData.debateId);
    }
});

// ページ離脱時にクリーンアップ
window.addEventListener('beforeunload', () => {
    if (window.eventListener) {
        window.eventListener.cleanup();
    }
});
