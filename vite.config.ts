import react from '@vitejs/plugin-react-swc'
import laravel from 'laravel-vite-plugin'
import { visualizer } from 'rollup-plugin-visualizer'
import { defineConfig } from 'vite'
import macrosPlugin from 'vite-plugin-babel-macros'

export default defineConfig({
    plugins: [
        react(),
        laravel(['resources/scripts/main.tsx']),
        macrosPlugin(),
        visualizer({
            filename: './public/stats.html',
        }),
    ],
    build: {
        target: ['es2020'],
    },
    optimizeDeps: {
        esbuildOptions: {
            target: 'es2020',
        },
    },
    server: {
        port: 1234,
        hmr: {
            host: 'localhost',
        },
    },
    resolve: {
        alias: {
            '@': '/resources/scripts',
        },
    },
})
