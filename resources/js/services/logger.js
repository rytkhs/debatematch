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
        try {
            // Viteの環境変数を最優先で使用（ブラウザ環境）
            if (typeof import.meta !== 'undefined' && import.meta.env) {
                // カスタム環境変数（VITE_APP_ENV）があれば最優先
                if (import.meta.env.VITE_APP_ENV) {
                    return import.meta.env.VITE_APP_ENV;
                }

                // Viteの標準的な環境判定
                if (import.meta.env.PROD) {
                    return 'production';
                }
                if (import.meta.env.DEV) {
                    return 'development';
                }

                // Viteのモード設定
                if (import.meta.env.MODE) {
                    return import.meta.env.MODE;
                }
            }

            // HTML の meta タグから環境変数を取得（Laravel Bladeテンプレートから）
            if (typeof document !== 'undefined') {
                const envMeta = document.querySelector('meta[name="app-env"]');
                if (envMeta) {
                    const envValue = envMeta.getAttribute('content');
                    if (envValue && envValue.trim()) {
                        return envValue.trim();
                    }
                }
            }

            // Node.js環境での環境変数取得（SSR時やテスト環境）
            // ブラウザ環境では process は存在しないため、存在チェックを厳密に行う
            if (
                typeof process !== 'undefined' &&
                process &&
                typeof process.env === 'object' &&
                process.env !== null
            ) {
                return process.env.NODE_ENV || 'development';
            }

            // ブラウザ環境での最終的なフォールバック（hostname判定）
            if (typeof window !== 'undefined' && window.location) {
                const hostname = window.location.hostname;
                if (
                    hostname === 'localhost' ||
                    hostname === '127.0.0.1' ||
                    hostname.startsWith('192.168.') ||
                    hostname.startsWith('10.') ||
                    hostname.startsWith('172.') ||
                    hostname.includes('.local') ||
                    hostname.includes('dev.') ||
                    hostname.endsWith('.dev')
                ) {
                    return 'development';
                } else if (
                    hostname.includes('test.') ||
                    hostname.includes('staging.') ||
                    hostname.includes('stg.') ||
                    hostname.endsWith('.test') ||
                    hostname.endsWith('.staging')
                ) {
                    return 'test';
                } else {
                    return 'production';
                }
            }
        } catch (error) {
            // 環境変数取得でエラーが発生した場合のフォールバック
            console.warn('[Logger] Environment detection failed:', error);
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
        const info = {
            namespace: this.namespace,
            environment: this._getEnvironment(),
            isProduction: this.isProduction,
            runtime: {
                hasProcess: typeof process !== 'undefined',
                hasWindow: typeof window !== 'undefined',
                hasDocument: typeof document !== 'undefined',
                hasImportMeta: typeof import.meta !== 'undefined',
            },
            hostname:
                typeof window !== 'undefined' && window.location
                    ? window.location.hostname
                    : 'unknown',
        };

        // Vite環境変数の詳細情報（開発環境でのみ）
        if (!this.isProduction && typeof import.meta !== 'undefined' && import.meta.env) {
            info.viteEnv = {
                MODE: import.meta.env.MODE || 'not set',
                DEV: import.meta.env.DEV,
                PROD: import.meta.env.PROD,
                VITE_APP_ENV: import.meta.env.VITE_APP_ENV || 'not set',
                BASE_URL: import.meta.env.BASE_URL || 'not set',
            };
        }

        // Node.js環境変数の情報（利用可能な場合のみ）
        if (
            !this.isProduction &&
            typeof process !== 'undefined' &&
            process &&
            typeof process.env === 'object'
        ) {
            info.nodeEnv = {
                NODE_ENV: process.env.NODE_ENV || 'not set',
            };
        }

        // HTML meta タグの情報
        if (!this.isProduction && typeof document !== 'undefined') {
            const envMeta = document.querySelector('meta[name="app-env"]');
            info.htmlMeta = {
                appEnv: envMeta ? envMeta.getAttribute('content') : 'not found',
            };
        }

        return info;
    }
}
