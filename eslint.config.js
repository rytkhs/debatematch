import js from '@eslint/js';
import prettier from 'eslint-plugin-prettier';
import prettierConfig from 'eslint-config-prettier';

export default [
    js.configs.recommended,
    {
        files: ['resources/js/**/*.js'],
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
                confirm: 'readonly',
                alert: 'readonly',
                prompt: 'readonly',
                fetch: 'readonly',
                navigator: 'readonly',
                location: 'readonly',
                localStorage: 'readonly',
                sessionStorage: 'readonly',
                MutationObserver: 'readonly',

                // Node.js環境
                process: 'readonly',

                // Laravel/Vite関連
                Vite: 'readonly',

                // プロジェクト固有のグローバル変数
                Echo: 'readonly',
                Pusher: 'readonly',
                Alpine: 'readonly',
                Livewire: 'readonly',

                // 翻訳・設定データ
                translations: 'readonly',
                debateData: 'readonly',
                roomCreateConfig: 'readonly',
                aiDebateCreateConfig: 'readonly',
            },
        },
        plugins: {
            prettier: prettier,
        },
        rules: {
            // Prettier統合
            'prettier/prettier': 'error',

            // 基本的なコード品質ルール
            'no-unused-vars': 'warn',
            'no-console': 'warn',
            'no-debugger': 'error',

            // ES6+ ベストプラクティス
            'prefer-const': 'error',
            'no-var': 'error',
            'prefer-arrow-callback': 'warn',

            'no-undef': 'error',
            'no-duplicate-imports': 'error',
            'no-unused-expressions': 'warn',
        },
    },
    // Jest テストファイル用の設定
    {
        files: ['tests/js/**/*.test.js', 'tests/js/setup.js'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                // Jest globals
                describe: 'readonly',
                test: 'readonly',
                it: 'readonly',
                expect: 'readonly',
                beforeEach: 'readonly',
                afterEach: 'readonly',
                beforeAll: 'readonly',
                afterAll: 'readonly',
                jest: 'readonly',

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
                MutationObserver: 'readonly',

                // Node.js環境
                global: 'readonly',
                process: 'readonly',

                // プロジェクト固有のグローバル変数
                Echo: 'readonly',
                Pusher: 'readonly',
                Livewire: 'readonly',
            },
        },
        env: {
            node: true, // Node.js 環境を有効にする
        },
        plugins: {
            prettier: prettier,
        },
        rules: {
            // Prettier統合
            'prettier/prettier': 'error',

            // テスト用にルールを緩和
            'no-unused-vars': 'warn',
            'no-undef': 'error',
            'no-console': 'off', // テストでは console.log を許可
        },
    },
    prettierConfig,
];
