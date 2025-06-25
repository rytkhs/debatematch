import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Page entry points
                'resources/js/pages/welcome.js',
                'resources/js/pages/debate-show.js',
                'resources/js/pages/room-show.js',
                'resources/js/pages/room-create.js',
                'resources/js/pages/ai-debate-create.js',
                'resources/js/pages/records-index.js',
                'resources/js/pages/records-show.js',
            ],
            refresh: true,
        }),
    ],
});
