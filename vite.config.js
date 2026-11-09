import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, '.', '');
    const viteHost = '127.0.0.1';
    const vitePort = Number(env.VITE_PORT) || 5173;

    return {
        server: {
            host: viteHost,
            port: vitePort,
            strictPort: true,
            hmr: {
                host: viteHost,
                port: vitePort,
            },
            watch: {
                usePolling: true,
            },
        },
        plugins: [
            laravel({
                input: 'resources/js/app.js',
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
    };
});
