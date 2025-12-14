import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            devServer: process.env.VITE_DEV_SERVER_URL,
        }),
        tailwindcss(),
        vue(), 
    ],
    server: {
        host: '0.0.0.0',  
        port: 5173,       
        strictPort: true,  
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
