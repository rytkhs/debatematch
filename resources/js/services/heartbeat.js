import Logger from './logger';

/**
 * シンプルなハートビートサービス
 * ユーザーの接続状態をサーバーに定期的に報告する
 */
class HeartbeatService {
    constructor(options = {}) {
        this.logger = new Logger('Heartbeat');
        this.options = {
            interval: 30000, // 30秒ごとにハートビート送信
            endpoint: '/api/heartbeat',
            contextType: null, // 'room' または 'debate'
            contextId: null,
            ...options,
        };

        this.timerId = null;
        this.consecutiveFailures = 0;
        this.maxConsecutiveFailures = 3;
        this.isRunning = false;
    }

    /**
     * ハートビートの開始
     */
    start() {
        if (this.isRunning) return;

        if (!this.options.contextType || !this.options.contextId) {
            this.logger.error('ハートビート: contextTypeとcontextIdが必要です');
            return;
        }

        this.isRunning = true;
        this.sendHeartbeat();
        this.timerId = setInterval(() => this.sendHeartbeat(), this.options.interval);
        this.logger.log('ハートビート開始: 間隔', this.options.interval, 'ms');
    }

    /**
     * ハートビートの停止
     */
    stop() {
        if (this.timerId) {
            clearInterval(this.timerId);
            this.timerId = null;
        }
        this.isRunning = false;
        this.logger.log('ハートビート停止');
    }

    /**
     * ハートビートの送信
     */
    async sendHeartbeat() {
        if (!navigator.onLine) {
            this.logger.log('オフライン状態のためハートビートをスキップ');
            return;
        }

        try {
            const response = await fetch(this.options.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content'),
                },
                body: JSON.stringify({
                    context_type: this.options.contextType,
                    context_id: this.options.contextId,
                }),
            });

            if (!response.ok) {
                throw new Error(`ハートビート失敗: ${response.status}`);
            }

            this.consecutiveFailures = 0;
        } catch (error) {
            this.consecutiveFailures++;
            this.logger.error(
                `ハートビート失敗 (${this.consecutiveFailures}/${this.maxConsecutiveFailures}):`,
                error
            );

            // 連続失敗が上限に達した場合の処理
            if (this.consecutiveFailures >= this.maxConsecutiveFailures) {
                this.logger.warn('ハートビートの連続失敗: 接続が不安定な可能性があります');
                // ページがロードされているなら、Livewireイベントを発火
                if (window.Livewire) {
                    // window.Livewire.dispatch('heartbeat-failed');
                }
            }
        }
    }
}

window.HeartbeatService = HeartbeatService;

export default HeartbeatService;
