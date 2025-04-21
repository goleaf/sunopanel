import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    build: {
        // Enable minification for production
        minify: 'terser',
        // Configure Terser options for better minification
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        // Enable asset hashing for cache busting
        rollupOptions: {
            output: {
                entryFileNames: `assets/[name].[hash].js`,
                chunkFileNames: `assets/[name].[hash].js`,
                assetFileNames: `assets/[name].[hash].[ext]`,
                manualChunks: {
                    // Split vendor code into separate chunks for better caching
                    'vendor': ['alpinejs', 'axios', 'sortablejs'],
                },
            },
        },
        // Enable SSR builds for Livewire
        ssr: {
            noExternal: ['laravel-vite-plugin'],
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
            // Enable SSR
            ssr: true,
        }),
        tailwindcss(),
    ],
    // Configure any global Vite options here
    server: {
        hmr: {
            host: 'localhost',
        },
    },
    // Optimize chunks for better code-splitting
    optimizeDeps: {
        include: ['alpinejs', 'axios', 'sortablejs'],
    },
});
