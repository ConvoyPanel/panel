import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react-swc'
import macrosPlugin from 'vite-plugin-babel-macros'
import { visualizer } from 'rollup-plugin-visualizer'

export default defineConfig({
    plugins: [
        react({
            jsxImportSource: '@emotion/react',
        }),
        laravel(['resources/scripts/main.tsx']),
        macrosPlugin() /* , visualizer(), */,
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
