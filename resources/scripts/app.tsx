import '@/app.css'
import { ThemeProvider } from '@/providers/theme-provider.tsx'
import '@fontsource/geist-sans'
import { RouterProvider, createRouter } from '@tanstack/react-router'
import React from 'react'
import ReactDOM from 'react-dom/client'

import NotFound from '@/components/ui/Navigation/NotFound.tsx'

import { routeTree } from './routeTree.gen'


const router = createRouter({
    routeTree,
    defaultNotFoundComponent: NotFound,
})

declare module '@tanstack/react-router' {
    interface Register {
        router: typeof router
    }
}

ReactDOM.createRoot(document.getElementById('root') as HTMLElement).render(
    <React.StrictMode>
        <ThemeProvider defaultTheme='dark' storageKey='vite-ui-theme'>
            <RouterProvider router={router} />
        </ThemeProvider>
    </React.StrictMode>
)
