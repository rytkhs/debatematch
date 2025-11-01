/**
 * ディベートカウントダウンの状態管理
 * UIから分離された状態管理専用クラス
 */
class CountdownManager {
    constructor() {
        this.endTime = null;
        this.timer = null;
        this.listeners = new Set();
        this.isActive = false;
        this.lastKnownState = null;
        this.retryCount = 0;
        this.maxRetries = 3;
    }

    /**
     * タイマーを開始
     */
    start(endTimeSeconds) {
        this.stop(); // 既存のタイマーを停止

        try {
            this.endTime = endTimeSeconds * 1000;
            this.isActive = true;
            this.retryCount = 0;

            // カウントダウンタイマーを開始
            this.timer = setInterval(() => this.tick(), 1000);
            this.tick(); // 即時実行

            // console.log(`カウントダウン開始: ${new Date(this.endTime).toLocaleTimeString()}`);
        } catch (error) {
            console.error('カウントダウン開始エラー:', error);
            this.handleError();
        }
    }

    /**
     * タイマーを停止
     */
    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
        this.isActive = false;
        this.endTime = null;
        // console.log('カウントダウン停止');
    }

    /**
     * タイマーの状態を更新し、リスナーに通知
     */
    tick() {
        try {
            const now = Date.now();
            const distance = this.endTime - now;

            const timeData = {
                isRunning: distance > 0,
                distance: Math.max(0, distance),
                minutes: Math.floor((distance / 1000 / 60) % 60),
                seconds: Math.floor((distance / 1000) % 60),
                isWarning: distance <= 30000, // 残り30秒以下
                timestamp: now,
            };

            // 最後の既知の状態を保存
            this.lastKnownState = timeData;

            // リスナーに通知
            this.notifyListeners(timeData);

            // タイマーが終了した場合は自動停止
            if (!timeData.isRunning && this.isActive) {
                this.stop();
            }
        } catch (error) {
            console.error('カウントダウンティックエラー:', error);
            this.handleError();
        }
    }

    /**
     * エラーハンドリング
     */
    handleError() {
        this.retryCount++;

        if (this.retryCount <= this.maxRetries) {
            console.warn(`カウントダウンエラー、再試行中 (${this.retryCount}/${this.maxRetries})`);

            // 少し遅延してから再試行
            setTimeout(() => {
                if (this.endTime && this.isActive) {
                    this.tick();
                }
            }, 1000);
        } else {
            console.error('カウントダウンエラーの最大再試行回数に達しました');
            this.notifyError();
        }
    }

    /**
     * エラー状態をリスナーに通知
     */
    notifyError() {
        const errorData = {
            isRunning: false,
            distance: 0,
            minutes: 0,
            seconds: 0,
            isWarning: false,
            isError: true,
            timestamp: Date.now(),
        };

        this.notifyListeners(errorData);
    }

    /**
     * リスナーを追加
     */
    addListener(callback) {
        if (typeof callback !== 'function') {
            // console.error('カウントダウンリスナーは関数である必要があります');
            return;
        }

        this.listeners.add(callback);

        // 既存の状態がある場合は即座に通知
        if (this.lastKnownState) {
            try {
                callback(this.lastKnownState);
            } catch (error) {
                console.error('リスナー実行エラー:', error);
            }
        }
    }

    /**
     * リスナーを削除
     */
    removeListener(callback) {
        this.listeners.delete(callback);
    }

    /**
     * すべてのリスナーに通知
     */
    notifyListeners(timeData) {
        this.listeners.forEach(listener => {
            try {
                listener(timeData);
            } catch (error) {
                console.error('リスナー通知エラー:', error);
                // エラーが発生したリスナーを削除
                this.listeners.delete(listener);
            }
        });
    }

    /**
     * 現在の状態を取得
     */
    getCurrentState() {
        if (!this.endTime) {
            return this.lastKnownState;
        }

        try {
            const now = Date.now();
            const distance = this.endTime - now;

            return {
                isRunning: distance > 0,
                distance: Math.max(0, distance),
                minutes: Math.floor((distance / 1000 / 60) % 60),
                seconds: Math.floor((distance / 1000) % 60),
                isWarning: distance <= 30000, // 残り30秒以下
                timestamp: now,
            };
        } catch (error) {
            console.error('現在状態取得エラー:', error);
            return this.lastKnownState;
        }
    }

    /**
     * 状態を復旧する（再接続時など）
     */
    restoreState(endTimeSeconds) {
        if (!endTimeSeconds) return;

        try {
            const now = Date.now();
            const endTime = endTimeSeconds * 1000;

            // まだ有効な時間であれば復旧
            if (endTime > now) {
                this.start(endTimeSeconds);
                // console.log('カウントダウン状態を復旧しました');
            } else {
                // console.log('期限切れのため状態復旧をスキップしました');
                this.stop();
            }
        } catch (error) {
            console.error('状態復旧エラー:', error);
        }
    }

    /**
     * Livewireイベントを初期化（エラーハンドリング強化）
     */
    initLivewireEvents() {
        if (typeof window.Livewire === 'undefined') {
            console.warn('Livewire が利用できません');
            return;
        }

        try {
            // Livewireコンポーネントからのイベントを受信
            window.Livewire.on('turn-advanced', data => {
                try {
                    if (data.turnEndTime) {
                        this.start(data.turnEndTime);
                    } else {
                        this.stop();
                    }
                } catch (error) {
                    console.error('turn-advancedイベント処理エラー:', error);
                }
            });

            // Echo接続状態の監視
            if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                const pusher = window.Echo.connector.pusher;

                // 接続が復旧した際の処理
                pusher.connection.bind('connected', () => {
                    // console.log('Echo接続が復旧しました');
                    // 必要に応じて状態を同期
                });

                // 接続エラーの処理
                pusher.connection.bind('error', error => {
                    console.warn('Echo接続エラー:', error);
                });
            }
        } catch (error) {
            console.error('Livewireイベント初期化エラー:', error);
        }
    }

    /**
     * デバッグ情報を取得
     */
    getDebugInfo() {
        return {
            endTime: this.endTime,
            isActive: this.isActive,
            listenerCount: this.listeners.size,
            lastKnownState: this.lastKnownState,
            retryCount: this.retryCount,
            currentTime: Date.now(),
        };
    }
}

export default CountdownManager;
