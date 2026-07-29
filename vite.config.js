import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: Number(process.env.VITE_PORT || 5173),
        strictPort: true,
        cors: {
            origin: [
                /^http:\/\/localhost(:\d+)?$/,
                /^http:\/\/127\.0\.0\.1(:\d+)?$/,
            ],
        },
        origin: `http://localhost:${Number(process.env.VITE_PORT || 5173)}`,
        hmr: {
            host: 'localhost',
            port: Number(process.env.VITE_PORT || 5173),
            clientPort: Number(process.env.VITE_PORT || 5173),
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/main.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],
});
