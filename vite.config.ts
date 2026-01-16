import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

const port = 5175;
const isDdev = !!process.env.DDEV_PRIMARY_URL;
const ddevUrl = process.env.DDEV_PRIMARY_URL || '';
const ddevHostname = isDdev ? new URL(ddevUrl).hostname : 'localhost';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
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
    server: {
        host: '0.0.0.0',
        port: port,
        strictPort: true,
        origin: isDdev ? `${ddevUrl}:${port}` : `http://localhost:${port}`,
        cors: {
            origin: isDdev ? [ddevUrl, `${ddevUrl}:${port}`] : true,
            credentials: true,
        },
        hmr: {
            protocol: isDdev ? 'wss' : 'ws',
            host: ddevHostname,
            clientPort: port,
        },
    },
});
