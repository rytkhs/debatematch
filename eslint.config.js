import js from '@eslint/js';
import prettier from 'eslint-plugin-prettier';
import alpinejs from 'eslint-plugin-alpinejs';

export default [
    {
        // グローバルな設定
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                // ブラウザ環境
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                setTimeout: 'readonly',
                clearTimeout: 'readonly',
                setInterval: 'readonly',
                clearInterval: 'readonly',
                requestAnimationFrame: 'readonly',
                cancelAnimationFrame: 'readonly',
                fetch: 'readonly',
                navigator: 'readonly',
                confirm: 'readonly',
                alert: 'readonly',
                process: 'readonly',
                // Laravel Echo
                Echo: 'readonly',
                // Pusher
                Pusher: 'readonly',
                // Alpine.js
                Alpine: 'readonly',
                // Jest (テスト環境)
                describe: 'readonly',
                it: 'readonly',
                test: 'readonly',
                expect: 'readonly',
                beforeEach: 'readonly',
                afterEach: 'readonly',
                beforeAll: 'readonly',
                afterAll: 'readonly',
                jest: 'readonly',
            },
        },
        plugins: {
            prettier,
            alpinejs,
        },
        rules: {
            ...js.configs.recommended.rules,
            // Prettier連携
            'prettier/prettier': 'error',

            // Alpine.js関連は基本設定のみ

            // 一般的なJavaScriptルール
            'no-unused-vars': 'warn',
            'no-console': 'warn', // 開発時のデバッグでは警告レベル
            'prefer-const': 'error',
            'no-var': 'error',

            // Import/Export関連
            'no-undef': 'error',
        },
    },
    {
        // JavaScriptファイル用の設定
        files: ['resources/js/**/*.js'],
    },
    {
        // テストファイル用の設定
        files: ['resources/js/**/*.test.js', 'tests/js/**/*.js'],
        languageOptions: {
            globals: {
                // Jest環境のグローバル変数は上で定義済み
            },
        },
        rules: {
            'no-console': 'off', // テストではconsoleを許可
        },
    },
    {
        // 除外ファイル
        ignores: [
            'node_modules/**',
            'public/**',
            'vendor/**',
            'storage/**',
            'bootstrap/cache/**',
            'coverage/**',
        ],
    },
];
