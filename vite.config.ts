import { TanStackRouterVite } from '@tanstack/router-vite-plugin'
import react from '@vitejs/plugin-react-swc'
import laravel from 'laravel-vite-plugin'
import { defineConfig } from 'vite'


export default defineConfig({
    plugins: [
        react(),
        TanStackRouterVite({
            routesDirectory: 'resources/scripts/routes',
            generatedRouteTree: 'resources/scripts/routeTree.gen.ts',
        }),
        laravel(['resources/scripts/app.tsx']),
        // visualizer({
        //     filename: './public/stats.html',
        // }),
    ],
    server: {
        port: 3000,
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
