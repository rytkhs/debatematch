import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/welcome.js',
                'resources/js/debate-form.js',
                'resources/js/debate/countdown.js',
                'resources/js/debate/event-listener.js',
                'resources/js/debate/presence.js',
                'resources/js/debate/scroll.js',
                'resources/js/debate/ui.js',
                'resources/js/rooms-show.js',
                'resources/js/debate/notification.js',
            ],
            refresh: true,
        }),
    ],
});
