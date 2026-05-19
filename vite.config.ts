import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { createLogger, defineConfig } from 'vite';

const logger = createLogger();
const originalWarn = logger.warn.bind(logger);
logger.warn = (msg, options) => {
    // libheif-js references Node built-ins only in its WASM loading path, not in browser code
    if (msg.includes('libheif-js') && msg.includes('has been externalized')) return;
    originalWarn(msg, options);
};

export default defineConfig({
    customLogger: logger,
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
    build: {
        rollupOptions: {
            onwarn(warning, defaultHandler) {
                // VueUse ships #__PURE__ annotations in positions Rollup can't parse; harmless
                if (warning.code === 'INVALID_ANNOTATION' && warning.id?.includes('@vueuse')) return;
                defaultHandler(warning);
            },
        },
    },
});
