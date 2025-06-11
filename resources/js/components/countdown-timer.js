/**
 * カウントダウンタイマーのUI表示コンポーネント
 * 状態管理から分離されたUI専用クラス
 */
class CountdownTimer {
    constructor(countdownManager) {
        this.countdownManager = countdownManager;
        this.elements = this.initializeElements();
        this.setupEventListeners();
    }

    /**
     * DOM要素を初期化
     */
    initializeElements() {
        return {
            headerTimers: document.querySelectorAll('.countdown-timer'),
            participantTimers: document.querySelectorAll('.participant-countdown'),
            timerDisplays: document.querySelectorAll('[data-countdown-display]'),
        };
    }

    /**
     * イベントリスナーを設定
     */
    setupEventListeners() {
        // カウントダウンマネージャーからの状態変更を受信
        this.countdownManager.addListener(timeData => {
            this.updateDisplay(timeData);
        });
    }

    /**
     * タイマー表示を更新
     */
    updateDisplay(timeData) {
        const { isRunning, minutes, seconds, isWarning } = timeData;

        // 分と秒を2桁で表示
        const displayMinutes = String(minutes).padStart(2, '0');
        const displaySeconds = String(seconds).padStart(2, '0');
        const timeString = `${displayMinutes}:${displaySeconds}`;

        // 各タイマー要素を更新
        this.updateHeaderTimers(timeString, isRunning, isWarning);
        this.updateParticipantTimers(timeString, isRunning, isWarning);
        this.updateGenericTimers(timeString, isRunning, isWarning);
    }

    /**
     * ヘッダーのタイマーを更新
     */
    updateHeaderTimers(timeString, isRunning, isWarning) {
        this.elements.headerTimers.forEach(timer => {
            if (timer) {
                timer.textContent = timeString;

                // 警告状態のスタイル
                if (isWarning && isRunning) {
                    timer.classList.add('text-red-500', 'font-bold');
                    timer.classList.remove('text-gray-600');
                } else {
                    timer.classList.remove('text-red-500', 'font-bold');
                    timer.classList.add('text-gray-600');
                }
            }
        });
    }

    /**
     * 参加者リストのタイマーを更新
     */
    updateParticipantTimers(timeString, isRunning, isWarning) {
        this.elements.participantTimers.forEach(timer => {
            if (timer) {
                timer.textContent = timeString;

                // 警告状態のスタイル
                if (isWarning && isRunning) {
                    timer.classList.add('text-red-400', 'animate-pulse');
                } else {
                    timer.classList.remove('text-red-400', 'animate-pulse');
                }
            }
        });
    }

    /**
     * 汎用タイマー表示を更新
     */
    updateGenericTimers(timeString, isRunning, isWarning) {
        this.elements.timerDisplays.forEach(timer => {
            if (timer) {
                timer.textContent = timeString;

                // data属性に基づいてスタイルを適用
                const warningClass = timer.dataset.warningClass || 'text-red-500';
                const normalClass = timer.dataset.normalClass || 'text-gray-600';

                if (isWarning && isRunning) {
                    timer.classList.add(...warningClass.split(' '));
                    timer.classList.remove(...normalClass.split(' '));
                } else {
                    timer.classList.remove(...warningClass.split(' '));
                    timer.classList.add(...normalClass.split(' '));
                }
            }
        });
    }

    /**
     * 要素を再初期化（DOM変更後に呼び出し）
     */
    reinitialize() {
        this.elements = this.initializeElements();
    }
}

export default CountdownTimer;
