import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath } from 'node:url';

const esToolkitCompatBridge = (name) =>
    fileURLToPath(
        new URL(`./resources/js/vendor/es-toolkit-compat/${name}.js`, import.meta.url),
    );

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            'es-toolkit/compat/get': esToolkitCompatBridge('get'),
            'es-toolkit/compat/isPlainObject': esToolkitCompatBridge('isPlainObject'),
            'es-toolkit/compat/last': esToolkitCompatBridge('last'),
            'es-toolkit/compat/maxBy': esToolkitCompatBridge('maxBy'),
            'es-toolkit/compat/minBy': esToolkitCompatBridge('minBy'),
            'es-toolkit/compat/omit': esToolkitCompatBridge('omit'),
            'es-toolkit/compat/range': esToolkitCompatBridge('range'),
            'es-toolkit/compat/sortBy': esToolkitCompatBridge('sortBy'),
            'es-toolkit/compat/sumBy': esToolkitCompatBridge('sumBy'),
            'es-toolkit/compat/throttle': esToolkitCompatBridge('throttle'),
            'es-toolkit/compat/uniqBy': esToolkitCompatBridge('uniqBy'),
        },
    },
    optimizeDeps: {
        exclude: ['recharts', 'es-toolkit'],
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
