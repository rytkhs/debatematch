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
    prettierConfig,
];
