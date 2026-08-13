import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    server: {
        host: '127.0.0.1',
        hmr: {
            host: '127.0.0.1',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/app.jsx',
            ],
            refresh: true,
        }),
        // laravel({
        //     input: 'resources/js/app.jsx', // or your entry \resources\js\app.jsx
        //     refresh: true, // Enables full page reloads on Blade/PHP changes
        // }),
        react(),
    ],
});
