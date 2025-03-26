/**
 * ディベートカウントダウンを管理する単一のグローバルクラス
 */
class DebateCountdown {
    constructor() {
        this.endTime = null;
        this.timer = null;
        this.listeners = new Set();
    }

    /**
     * タイマーを開始
     */
    start(endTimeSeconds) {
        this.stop(); // 既存のタイマーを停止
        this.endTime = endTimeSeconds * 1000;

        // カウントダウンタイマーを開始
        this.timer = setInterval(() => this.tick(), 1000);
        this.tick(); // 即時実行
    }

    /**
     * タイマーを停止
     */
    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }

    /**
     * タイマーの状態を更新し、リスナーに通知
     */
    tick() {
        const now = Date.now();
        const distance = this.endTime - now;

        let timeData = {
            isRunning: distance > 0,
            distance: Math.max(0, distance),
            minutes: Math.floor((distance / 1000 / 60) % 60),
            seconds: Math.floor((distance / 1000) % 60),
            isWarning: distance <= 30000 // 残り30秒以下
        };

        // リスナーに通知
        this.notifyListeners(timeData);
    }

    /**
     * リスナーを追加
     */
    addListener(callback) {
        this.listeners.add(callback);
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
        this.listeners.forEach(listener => listener(timeData));
    }
}

// グローバルなカウントダウンインスタンスを作成
window.debateCountdown = new DebateCountdown();

document.addEventListener('livewire:initialized', () => {
    // Livewireコンポーネントからのイベントを受信
    window.Livewire.on('turn-advanced', (data) => {
        if (data.turnEndTime) {
            window.debateCountdown.start(data.turnEndTime);
        } else {
            window.debateCountdown.stop();
        }
    });
});

export default window.debateCountdown;
