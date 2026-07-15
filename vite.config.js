import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/stats.js', 'resources/js/users.js', 'resources/js/chat.js', 'resources/js/live.js'],
            refresh: true,
            // Polices self-hostées (public/fonts + @font-face dans app.css) : plus de fetch réseau au build.
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
