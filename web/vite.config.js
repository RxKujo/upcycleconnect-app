import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // Écouter sur toutes les interfaces réseau (requis dans Docker)
        host: '0.0.0.0',
        port: 5173,
        // HMR : le navigateur se connecte à localhost (machine hôte)
        // même si Vite tourne dans un container Docker
        hmr: {
            host: 'localhost',
            port: 5173,
        },
        watch: {
            // usePolling requis sur Windows/Mac car les événements
            // filesystem ne se propagent pas depuis Docker
            usePolling: true,
            interval: 300,
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
