import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';
import { readFileSync } from 'fs';
import { homedir } from 'os';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament/admin/theme.css',
                'resources/js/app.js',
                'resources/js/filament/billing/stripe-handler.js',
            ],
            refresh: false
            // [
            //     ...refreshPaths,
            //     'app/Livewire/**',
            //     'app/Filament/**',
            // ]
            ,
        }),
    ],
    server: {
        https: {
            key: readFileSync(`${homedir()}/.config/valet/Certificates/mixpitch.test.key`),
            cert: readFileSync(`${homedir()}/.config/valet/Certificates/mixpitch.test.crt`),
        },
        host: 'mixpitch.test',
    },
    build: {
        rollupOptions: {
            external: [],
            output: {
                assetFileNames: (assetInfo) => {
                    // Keep font files in the webfonts directory
                    if (assetInfo.name && /\.(woff2?|eot|ttf|otf)(\?.*)?$/i.test(assetInfo.name)) {
                        return 'webfonts/[name].[ext]';
                    }
                    return 'assets/[name]-[hash].[ext]';
                }
            }
        }
    }
});
