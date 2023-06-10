import { FunctionComponent, lazy, LazyExoticComponent, ReactNode } from 'react'
import Spinner from '@/components/elements/Spinner'
import { createBrowserRouter, Outlet } from 'react-router-dom'
import AuthenticatedRoutes from '@/routers/middleware/AuthenticatedRoutes'
import { NotFound } from '@/components/elements/ScreenBlock'
import GuestRoutes from '@/routers/middleware/GuestRoutes'
import NavigationBar from '@/components/elements/navigation/NavigationBar'
import NavigationBarProvider from '@/components/NavigationBarProvider'
import { routes as adminRoutes } from '@/routers/AdminDashboardRouter'
import { routes as clientRoutes } from '@/routers/DashboardRouter'

export interface Route {
    path: string
    component: FunctionComponent<{ children?: ReactNode }>
    children?: Route[]
}

export const lazyLoad = (LazyElement: LazyExoticComponent<() => JSX.Element>) => {
    return (
        <Spinner.Suspense>
            <LazyElement />
        </Spinner.Suspense>
    )
}

const router = createBrowserRouter([
    {
        path: '/auth',
        element: (
            <GuestRoutes>
                <Outlet />
            </GuestRoutes>
        ),
        children: [
            {
                path: 'login',
                element: lazyLoad(lazy(() => import('@/components/auth/LoginContainer'))),
            },
        ],
    },
    {
        path: '/',
        element: (
            <AuthenticatedRoutes>
                <NavigationBarProvider>
                    <NavigationBar />
                    <Outlet />
                </NavigationBarProvider>
            </AuthenticatedRoutes>
        ),
        children: [...clientRoutes, ...adminRoutes],
    },
    {
        path: '*',
        element: <NotFound full />,
    },
])

export default router
