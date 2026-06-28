/// <reference types="vite/client" />
import '@/app.css'
import { ThemeProvider } from '@/providers/theme-provider.tsx'
import '@fontsource/geist-sans/400.css'
import '@fontsource/geist-sans/500.css'
import '@fontsource/geist-sans/600.css'
import '@fontsource/geist-sans/700.css'
import '@fontsource/geist-sans/800.css'
import { QueryClientProvider } from '@tanstack/react-query'
import { ReactQueryDevtools } from '@tanstack/react-query-devtools'
import { RouterProvider, createRouter } from '@tanstack/react-router'
import React from 'react'
import ReactDOM from 'react-dom/client'

import ErrorComponent from '@/components/ui/Navigation/ErrorPages/ErrorComponent.tsx'
import NotFoundComponent from '@/components/ui/Navigation/ErrorPages/NotFoundComponent.tsx'
import { queryClient } from '@/lib/query-client.ts'

import { routeTree } from './routeTree.gen'

const router = createRouter({
    routeTree,
    context: { queryClient },
    defaultNotFoundComponent: NotFoundComponent,
    defaultErrorComponent: ErrorComponent,
})

declare module '@tanstack/react-router' {
    interface Register {
        router: typeof router
    }

    interface StaticDataRouteOption {
        title?: string
    }
}

ReactDOM.createRoot(document.getElementById('root') as HTMLElement).render(
    <React.StrictMode>
        <QueryClientProvider client={queryClient}>
            <ThemeProvider storageKey='theme'>
                <RouterProvider router={router} />
            </ThemeProvider>
            {import.meta.env.DEV && <ReactQueryDevtools buttonPosition='bottom-left' />}
        </QueryClientProvider>
    </React.StrictMode>
)
