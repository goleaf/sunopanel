import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/components/track-status.css',
                'resources/js/app.js',
                'resources/js/tracks.js',
                'resources/js/youtube-stats.js',
                'resources/js/youtube-toggle.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
