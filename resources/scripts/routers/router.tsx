import { routes as adminRoutes } from '@/routers/AdminDashboardRouter'
import { routes as clientRoutes } from '@/routers/DashboardRouter'
import { lazyLoad } from '@/routers/helpers'
import AuthenticatedRoutes from '@/routers/middleware/AuthenticatedRoutes'
import GuestRoutes from '@/routers/middleware/GuestRoutes'
import { ReactNode, lazy } from 'react'
import { Outlet, RouteObject, createBrowserRouter } from 'react-router-dom'

import recordPageView from '@/api/telemetry/recordPageView'

import { NotFound } from '@/components/elements/ScreenBlock'
import NavigationBar from '@/components/elements/navigation/NavigationBar'

import NavigationBarProvider from '@/components/NavigationBarProvider'
import AdminNotices from '@/components/admin/announcements/AdminNotices'


export type Route = {
    handle?: Handle
    children?: Route[]
} & Omit<RouteObject, 'handle' | 'children'>

export interface Handle {
    crumb: (data: any) => {
        to: string
        element: ReactNode
    }
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
                element: lazyLoad(
                    lazy(() => import('@/components/auth/LoginContainer'))
                ),
            },
        ],
    },
    {
        path: '/',
        element: (
            <AuthenticatedRoutes>
                <NavigationBarProvider>
                    <NavigationBar />
                    <AdminNotices />
                    <Outlet />
                </NavigationBarProvider>
            </AuthenticatedRoutes>
        ),
        children: [...clientRoutes, ...adminRoutes] as RouteObject[],
    },
    {
        path: '*',
        element: <NotFound full />,
    },
])

// Counts page views for the daily telemetry report. Once each navigation
// settles, the route pattern of the page (`/servers/:id/backups`, never the
// address itself) goes to the panel, which keeps a count per page; the panel
// leaves it out of the page when telemetry is off.
if (
    (window as Window & { SiteConfiguration?: { telemetry?: boolean } })
        .SiteConfiguration?.telemetry
) {
    let counted: string | undefined

    const countPageView = (state: typeof router.state) => {
        if (
            !state.initialized ||
            state.navigation.state !== 'idle' ||
            state.location.pathname === counted
        ) {
            return
        }

        counted = state.location.pathname

        const pattern =
            '/' +
            state.matches
                .flatMap(match => match.route.path?.split('/') ?? [])
                .filter(Boolean)
                .join('/')

        if (!pattern.startsWith('/auth')) {
            recordPageView(pattern).catch(() => {})
        }
    }

    countPageView(router.state)
    router.subscribe(countPageView)
}

export default router