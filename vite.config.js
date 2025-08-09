import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/index.css', 'resources/js/index.js'],
        }),
        tailwindcss(),
    ],
    build: {
        outDir: 'resources/dist',
        emptyOutDir: true,
        rollupOptions: {
            output: {
                entryFileNames: 'js/[name].js',
                assetFileNames: 'css/[name].css',
                manualChunks: (id) => {
                    if (id.includes('resources/js/index.js')) {
                        return 'index'
                    }
                },
            },
        },
    },
})
