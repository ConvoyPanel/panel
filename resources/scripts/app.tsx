import '@/app.css'
import { ThemeProvider } from '@/providers/theme-provider.tsx'
import '@fontsource/geist-sans/400.css'
import '@fontsource/geist-sans/500.css'
import '@fontsource/geist-sans/600.css'
import '@fontsource/geist-sans/700.css'
import '@fontsource/geist-sans/800.css'
import { RouterProvider, createRouter } from '@tanstack/react-router'
import React from 'react'
import ReactDOM from 'react-dom/client'

import ErrorComponent from '@/components/ui/Navigation/ErrorPages/ErrorComponent.tsx'
import NotFoundComponent from '@/components/ui/Navigation/ErrorPages/NotFoundComponent.tsx'

import { routeTree } from './routeTree.gen'

declare module '@tanstack/react-router' {
    interface Register {
        router: typeof router
    }

    interface StaticDataRouteOption {
        title?: string
    }
}

const router = createRouter({
    routeTree,
    defaultNotFoundComponent: NotFoundComponent,
    defaultErrorComponent: ErrorComponent,
})

ReactDOM.createRoot(document.getElementById('root') as HTMLElement).render(
    <React.StrictMode>
        <ThemeProvider storageKey='theme'>
            <RouterProvider router={router} />
        </ThemeProvider>
    </React.StrictMode>
)
