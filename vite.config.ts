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
    build: {
        rollupOptions: {
            output: {
                // Peel stable, always-eager vendors out of the entry chunk into
                // long-cached chunks so routine app edits don't bust their cache
                // and the initial load can fetch them in parallel. Deliberately
                // narrow: lazy-only deps (react-table, recharts, dnd-kit, …) fall
                // through so they stay in their route/feature chunks.
                manualChunks(id) {
                    if (!id.includes('/node_modules/')) return
                    if (/\/node_modules\/(react|react-dom|scheduler)\//.test(id))
                        return 'react-vendor'
                    if (
                        /\/node_modules\/@tanstack\/(react-router|router-core|history|react-query|query-core)\//.test(
                            id
                        )
                    )
                        return 'tanstack'
                    if (/\/node_modules\/zod\//.test(id)) return 'validation'
                    if (/\/node_modules\/axios\//.test(id)) return 'http'
                },
            },
        },
    },
})
