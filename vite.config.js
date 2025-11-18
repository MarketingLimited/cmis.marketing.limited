import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        // Optimize bundle size
        rollupOptions: {
            output: {
                manualChunks: {
                    'alpine': ['alpinejs'],
                    'chart': ['chart.js'],
                    'vendor': ['axios'],
                },
            },
        },
        // Reduce chunk size warnings threshold
        chunkSizeWarningLimit: 1000,
        // Enable minification
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.logs in production
            },
        },
    },
    // Optimize dependencies
    optimizeDeps: {
        include: ['alpinejs', 'chart.js', 'axios'],
    },
});
