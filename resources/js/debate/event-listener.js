/**
 * ディベート評価通知用モジュール
 */
class EventListener {
    constructor(debateId) {
        this.debateId = debateId;
        this.channel = null;
        this.initEchoListeners();
    }

    /**
     * Echo リスナーを初期化
     */
    initEchoListeners() {
        if (!window.Echo || !this.debateId) {
            console.error('Echo または debateId が設定されていません');
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
                console.log('DebateTerminated イベントを受信:', e);
                this.handleDebateTerminated(e);
            });
    }

    /**
     * ディベート終了イベントの処理
     */
    handleDebateFinished(event) {
        // 終了通知を表示
        this.showNotification({
            title: 'ディベートが終了しました',
            message: 'AIによる評価を行っています。しばらくお待ちください...',
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
            title: 'ディベート評価が完了しました',
            message: '結果ページへ移動します',
            type: 'success',
            duration: 5000
        });
        console.log(event.debateId);

        // 評価結果ページへのURLを生成
        const resultUrl = `/debate/${this.debateId}/result`;

        // 5秒後に評価結果ページへリダイレクト（複数回実行保証）
        setTimeout(() => {
            // 1. 通常のリダイレクト
            window.location.href = resultUrl;

            // 2. replaceを使ったリダイレクト
            window.location.replace(resultUrl);

            // 3. 念のため、500ms後に再度リダイレクトチェック
            setTimeout(() => {
                if (window.location.pathname !== resultUrl) {
                    window.location.href = resultUrl;
                }
            }, 500);

        }, 5000);
    }

    /**c
     * ディベート強制終了イベントの処理
     */
    handleDebateTerminated(event) {
        // 終了通知を表示
        // this.showTerminationNotification();
        alert('相手との接続が切断されたため、ディベートを終了します');
        // welcomeページへリダイレクト
        window.location.href = '/';
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
        notificationElement.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50
            ${options.type === 'success' ? 'bg-green-50 border-green-500' :
            options.type === 'error' ? 'bg-red-50 border-red-500' :
            'bg-blue-50 border-blue-500'} border-l-4`;

        notificationElement.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    <span class="material-icons ${
                        options.type === 'success' ? 'text-green-600' :
                        options.type === 'error' ? 'text-red-600' :
                        'text-blue-600'
                    }">
                        ${
                            options.type === 'success' ? 'check_circle' :
                            options.type === 'error' ? 'error' :
                            'info'
                        }
                    </span>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium ${
                        options.type === 'success' ? 'text-green-800' :
                        options.type === 'error' ? 'text-red-800' :
                        'text-blue-800'
                    }">${options.title}</h3>
                    <div class="mt-1 text-sm ${
                        options.type === 'success' ? 'text-green-700' :
                        options.type === 'error' ? 'text-red-700' :
                        'text-blue-700'
                    }">${options.message}</div>
                </div>
            </div>
        `;

        document.body.appendChild(notificationElement);

        // options.duration後に通知を消す
        setTimeout(() => {
            notificationElement.remove();
        }, options.duration || 5000);
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
                    <h2 class="text-2xl font-bold text-gray-900 mb-2 tracking-tight">ディベート終了</h2>
                    <p class="text-gray-600 mb-6 leading-relaxed">ディベートが終了しました。現在、AIが評価を行っています...</p>
                    <div class="flex items-center justify-center space-x-3 mb-8">
                        <div class="w-3 h-3 bg-primary rounded-full animate-pulse" style="animation-delay: 0s"></div>
                        <div class="w-4 h-4 bg-primary rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
                        <div class="w-3 h-3 bg-primary rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
                    </div>
                    <a id="result-page-link" href="${window.location.origin}/debate/${this.debateId}/result"
                       class="hidden inline-flex items-center justify-center bg-primary text-white font-bold py-3 px-8 rounded-lg transition duration-300 transform hover:scale-105 hover:shadow-lg">
                        <span class="material-icons mr-2">analytics</span>
                        結果ページへ
                    </a>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        // 20秒後に結果ページへのリンクを表示
        setTimeout(() => {
            const resultLink = document.getElementById('result-page-link');
            if (resultLink) {
                resultLink.classList.remove('hidden');
            }
        }, 20000);

    }


    /**
     * リソースをクリーンアップ
     */
    cleanup() {
        if (this.channel) {
            this.channel.stopListening('DebateFinished');
            this.channel.stopListening('DebateEvaluated');
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
