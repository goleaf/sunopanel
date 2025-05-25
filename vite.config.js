import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/tracks.js',
                'resources/js/track-status.js',
                'resources/js/youtube-stats.js',
                'resources/js/youtube-toggle.js',
                'resources/js/queue-dashboard.js',
                'resources/js/youtube-analytics.js',
                'resources/js/modules/trackStatusManager.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['axios'],
                    tracks: [
                        'resources/js/tracks.js',
                        'resources/js/track-status.js',
                        'resources/js/modules/trackStatusManager.js'
                    ],
                    youtube: [
                        'resources/js/youtube-stats.js',
                        'resources/js/youtube-toggle.js'
                    ]
                }
            }
        },
        sourcemap: false,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
    },
    server: {
        hmr: {
            host: 'localhost',
        },
    },
});
