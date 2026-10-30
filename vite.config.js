import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/site.scss',
                'resources/scss/admin.scss',
                'resources/js/site.js',
                'resources/js/admin.js',
                'resources/js/api-docs.js',
            ],
            refresh: true,
            fonts: [
                bunny('Fraunces', { weights: [500, 600, 700] }),
                bunny('Inter', { weights: [400, 500, 600, 700] }),
            ],
        }),
    ],
    css: {
        preprocessorOptions: {
            // Bootstrap 5.3 still uses Sass @import and global functions.
            scss: { quietDeps: true, silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'if-function'] },
        },
    },
    build: {
        chunkSizeWarningLimit: 1600, // only the API docs page loads the large Swagger UI chunk
    },
    server: {
        watch: { ignored: ['**/storage/framework/views/**'] },
    },
});
