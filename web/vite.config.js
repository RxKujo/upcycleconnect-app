import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/conteneurs-map.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        origin: 'http://localhost:5173',
        // L'app Laravel est servie depuis :8000 (origine différente du serveur Vite :5173).
        // Les modules ES sont chargés en CORS : sans cette autorisation, Vite 7 refuse
        // les requêtes cross-origin et tout le JS de dev (app.js, conteneurs-map.js…) est bloqué.
        cors: {
            origin: /^https?:\/\/(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
