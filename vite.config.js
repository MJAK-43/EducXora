import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const clientPort = Number(env.VITE_PORT || 5173);
    const appUrl = env.APP_URL || 'http://localhost:8080';

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.ts'],
                refresh: true,
            }),
            vue(),
        ],
        resolve: {
            alias: {
                '@': '/resources/js',
            },
        },
        server: {
            host: '0.0.0.0',
            port: 5173,
            origin: `http://localhost:${clientPort}`,
            cors: {
                origin: appUrl,
            },
            hmr: {
                host: 'localhost',
                clientPort,
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
