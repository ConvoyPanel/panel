import { TanStackRouterVite } from '@tanstack/router-plugin/vite'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react-swc'
import laravel from 'laravel-vite-plugin'
import { visualizer } from 'rollup-plugin-visualizer'
import { defineConfig } from 'vite'


// ddev injects DDEV_PRIMARY_URL (e.g. https://convoy.ddev.site) into the web
// container. When present, serve Vite over the exposed :3000 https port and
// point HMR at the ddev host; otherwise fall back to localhost.
const ddevHost = process.env.DDEV_PRIMARY_URL
    ? new URL(process.env.DDEV_PRIMARY_URL).hostname
    : undefined

export default defineConfig({
    plugins: [
        react(),
        tailwindcss(),
        TanStackRouterVite({
            routesDirectory: 'resources/scripts/routes',
            generatedRouteTree: 'resources/scripts/routeTree.gen.ts',
        }),
        laravel(['resources/scripts/app.tsx']),
        // @ts-expect-error
        visualizer(),
    ],
    server: {
        host: '0.0.0.0',
        port: 3000,
        strictPort: true,
        ...(ddevHost
            ? {
                  origin: `https://${ddevHost}:3000`,
                  cors: true,
                  hmr: { host: ddevHost, protocol: 'wss', clientPort: 3000 },
              }
            : {
                  hmr: { host: 'localhost' },
              }),
    },
    resolve: {
        alias: {
            '@': '/resources/scripts',
        },
    },
})
