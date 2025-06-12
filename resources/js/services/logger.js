/**
 * 環境に応じたログ出力を制御するモジュール
 * @class Logger
 * @param {string} namespace - ログの名前空間
 * @example
 * const logger = new Logger('MyModule');
 * logger.log('Debug message');
 * logger.warn('Warning message');
 * logger.error('Error message');
 */
export default class Logger {
    constructor(namespace) {
        this.namespace = namespace;
        this.isProduction = this._getEnvironment() === 'production';
    }

    /**
     * 環境変数を安全に取得
     * @private
     * @returns {string} 環境名 ('production', 'development', 'test', etc.)
     */
    _getEnvironment() {
        // Node.js環境（テスト環境含む）
        if (typeof process !== 'undefined' && process.env) {
            return process.env.NODE_ENV || 'development';
        }

        // Vite環境変数の確認（ブラウザ環境）
        if (typeof window !== 'undefined') {
            // ViteのHMR環境チェック
            if (window.__vite_plugin_react_preamble_installed__) {
                return 'development';
            }

            // Viteの開発サーバーの存在確認
            if (window.__vite_is_modern_browser || window.__HMR_PORT__) {
                return 'development';
            }
        }

        // Viteの環境変数（window経由）
        if (typeof window !== 'undefined' && window.__VITE_ENV__) {
            return window.__VITE_ENV__;
        }

        // HTML の meta タグから環境変数を取得
        if (typeof document !== 'undefined') {
            const envMeta = document.querySelector('meta[name="app-env"]');
            if (envMeta) {
                return envMeta.getAttribute('content') || 'development';
            }
        }

        // 最終的なフォールバック（本番環境の判定）
        if (typeof window !== 'undefined') {
            // location.hostnameで本番環境を判定
            const hostname = window.location.hostname;
            if (
                hostname.includes('localhost') ||
                hostname.includes('127.0.0.1') ||
                hostname.includes('dev')
            ) {
                return 'development';
            } else if (hostname.includes('test') || hostname.includes('staging')) {
                return 'test';
            } else {
                return 'production';
            }
        }

        // すべて失敗した場合のデフォルト
        return 'development';
    }

    /**
     * デバッグ情報をログ出力
     * @public
     * @param {...*} args - ログに出力する引数
     */
    log(...args) {
        if (!this.isProduction) {
            console.log(`[${this.namespace}]`, ...args);
        }
    }

    /**
     * 警告メッセージをログ出力
     * @public
     * @param {...*} args - ログに出力する引数
     */
    warn(...args) {
        if (!this.isProduction) {
            console.warn(`[${this.namespace}]`, ...args);
        }
    }

    /**
     * エラーメッセージをログ出力（本番環境でも出力）
     * @public
     * @param {...*} args - ログに出力する引数
     */
    error(...args) {
        // エラーは本番環境でも出力
        console.error(`[${this.namespace}]`, ...args);
    }

    /**
     * 現在の環境情報を取得（デバッグ用）
     * @public
     * @returns {Object} 環境情報
     */
    getEnvironmentInfo() {
        return {
            namespace: this.namespace,
            environment: this._getEnvironment(),
            isProduction: this.isProduction,
            hasProcess: typeof process !== 'undefined',
            hasWindow: typeof window !== 'undefined',
            hostname: typeof window !== 'undefined' ? window.location.hostname : 'unknown',
        };
    }
}
