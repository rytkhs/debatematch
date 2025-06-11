/**
 * 環境に応じたログ出力を制御するモジュール
 */
export default class Logger {
    constructor(namespace) {
        this.namespace = namespace;
        this.isProduction = process.env.NODE_ENV === 'production';
    }

    log(...args) {
        if (!this.isProduction) {
            console.log(`[${this.namespace}]`, ...args);
        }
    }

    warn(...args) {
        if (!this.isProduction) {
            console.warn(`[${this.namespace}]`, ...args);
        }
    }

    error(...args) {
        // エラーは本番環境でも出力
        console.error(`[${this.namespace}]`, ...args);
    }
}
