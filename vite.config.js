import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/jeopardy.css',
                'resources/js/app.js',
                'resources/js/buzzer-handler.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        host: true,
        port: 5173,
        hmr: {
            host: 'sierra.local',
            protocol: 'http',
            clientPort: 5173,
        },
    },
});
