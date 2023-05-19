import { FunctionComponent, lazy, LazyExoticComponent, ReactNode } from 'react'
import { ServerContext } from '@/state/server'
import Spinner from '@/components/elements/Spinner'
import { createBrowserRouter, Outlet } from 'react-router-dom'
import AuthenticatedRoutes from '@/routers/middleware/AuthenticatedRoutes'
import { NotFound } from '@/components/elements/ScreenBlock'
import { NodeContext } from '@/state/admin/node'
import { AdminServerContext } from '@/state/admin/server'
import { AdminUserContext } from '@/state/admin/user'
import GuestRoutes from '@/routers/middleware/GuestRoutes'
import NavigationBar from '@/components/elements/navigation/NavigationBar'
import NavigationBarProvider from '@/components/NavigationBarProvider'

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
        children: [
            { index: true, element: lazyLoad(lazy(() => import('@/routers/DashboardRouter'))) },
            {
                element: (
                    <ServerContext.Provider>{lazyLoad(lazy(() => import('./ServerRouter')))}</ServerContext.Provider>
                ),
                path: '/servers/:id',
                children: [
                    {
                        index: true,
                        element: lazyLoad(lazy(() => import('@/components/servers/overview/ServerOverviewContainer'))),
                    },
                    {
                        path: 'backups',
                        element: lazyLoad(lazy(() => import('@/components/servers/backups/BackupsContainer'))),
                    },
                    {
                        path: 'settings',
                        element: lazyLoad(lazy(() => import('@/components/servers/settings/ServerSettingsContainer'))),
                        children: [
                            {
                                path: 'general',
                                element: lazyLoad(lazy(() => import('@/components/servers/settings/GeneralContainer'))),
                            },

                            {
                                path: 'hardware',
                                element: lazyLoad(
                                    lazy(() => import('@/components/servers/settings/HardwareContainer'))
                                ),
                            },

                            {
                                path: 'network',
                                element: lazyLoad(lazy(() => import('@/components/servers/settings/NetworkContainer'))),
                            },
                            {
                                path: 'security',
                                element: lazyLoad(
                                    lazy(() => import('@/components/servers/settings/SecurityContainer'))
                                ),
                            },
                        ],
                    },
                    {
                        path: 'terminal',
                        element: lazyLoad(lazy(() => import('@/components/servers/terminal/ServerTerminalContainer'))),
                    },
                ],
            },
            {
                path: '/admin',
                element: (
                    <AuthenticatedRoutes requireRootAdmin>
                        {lazyLoad(lazy(() => import('@/routers/AdminDashboardRouter')))}
                    </AuthenticatedRoutes>
                ),
                children: [
                    {
                        index: true,
                        element: lazyLoad(lazy(() => import('@/components/admin/overview/OverviewContainer'))),
                    },
                    {
                        path: 'locations',
                        element: lazyLoad(lazy(() => import('@/components/admin/locations/LocationsContainer'))),
                    },
                    {
                        path: 'nodes',
                        children: [
                            {
                                index: true,
                                element: lazyLoad(lazy(() => import('@/components/admin/nodes/NodesContainer'))),
                            },
                            {
                                path: ':id',
                                element: (
                                    <NodeContext.Provider>
                                        {lazyLoad(lazy(() => import('@/routers/AdminNodeRouter')))}
                                    </NodeContext.Provider>
                                ),
                                children: [
                                    {
                                        index: true,
                                        element: lazyLoad(
                                            lazy(
                                                () => import('@/components/admin/nodes/overview/NodeOverviewContainer')
                                            )
                                        ),
                                    },
                                    {
                                        path: 'servers',
                                        element: lazyLoad(
                                            lazy(() => import('@/components/admin/nodes/servers/NodeServersContainer'))
                                        ),
                                    },
                                    {
                                        path: 'isos',
                                        element: lazyLoad(
                                            lazy(
                                                () => import('@/components/admin/nodes/isoLibrary/IsoLibraryContainer')
                                            )
                                        ),
                                    },
                                    {
                                        path: 'templates',
                                        element: lazyLoad(
                                            lazy(
                                                () =>
                                                    import('@/components/admin/nodes/templates/NodeTemplatesContainer')
                                            )
                                        ),
                                    },
                                    {
                                        path: 'addresses',
                                        element: lazyLoad(
                                            lazy(
                                                () =>
                                                    import('@/components/admin/nodes/addresses/NodeAddressesContainer')
                                            )
                                        ),
                                    },
                                    {
                                        path: 'settings',
                                        element: lazyLoad(
                                            lazy(
                                                () => import('@/components/admin/nodes/settings/NodeSettingsContainer')
                                            )
                                        ),
                                        children: [
                                            {
                                                path: 'general',
                                                element: lazyLoad(
                                                    lazy(
                                                        () =>
                                                            import('@/components/admin/nodes/settings/GeneralContainer')
                                                    )
                                                ),
                                            },
                                        ],
                                    },
                                ],
                            },
                        ],
                    },
                    {
                        path: 'servers',
                        children: [
                            {
                                index: true,
                                element: lazyLoad(lazy(() => import('@/components/admin/servers/ServersContainer'))),
                            },
                            {
                                path: ':id',
                                element: (
                                    <AdminServerContext.Provider>
                                        {lazyLoad(lazy(() => import('@/routers/AdminServerRouter')))}
                                    </AdminServerContext.Provider>
                                ),
                                children: [
                                    {
                                        index: true,
                                        element: lazyLoad(
                                            lazy(
                                                () =>
                                                    import(
                                                        '@/components/admin/servers/overview/ServerOverviewContainer'
                                                    )
                                            )
                                        ),
                                    },
                                    {
                                        path: 'settings',
                                        element: lazyLoad(
                                            lazy(
                                                () =>
                                                    import(
                                                        '@/components/admin/servers/settings/ServerSettingsContainer'
                                                    )
                                            )
                                        ),
                                        children: [
                                            {
                                                path: 'general',
                                                element: lazyLoad(
                                                    lazy(
                                                        () =>
                                                            import(
                                                                '@/components/admin/servers/settings/GeneralContainer'
                                                            )
                                                    )
                                                ),
                                            },
                                            {
                                                path: 'hardware',
                                                element: lazyLoad(
                                                    lazy(
                                                        () =>
                                                            import(
                                                                '@/components/admin/servers/settings/partials/hardware/ServerHardwareContainer'
                                                            )
                                                    )
                                                ),
                                            },
                                        ],
                                    },
                                ],
                            },
                        ],
                    },
                    {
                        path: 'users',
                        children: [
                            {
                                index: true,
                                element: lazyLoad(lazy(() => import('@/components/admin/users/UsersContainer'))),
                            },
                            {
                                path: ':id',
                                element: (
                                    <AdminUserContext.Provider>
                                        {lazyLoad(lazy(() => import('./AdminUserRouter')))}
                                    </AdminUserContext.Provider>
                                ),
                                children: [
                                    {
                                        path: 'settings',
                                        element: lazyLoad(
                                            lazy(
                                                () => import('@/components/admin/users/settings/UserSettingsContainer')
                                            )
                                        ),
                                        children: [
                                            {
                                                path: 'general',
                                                element: lazyLoad(
                                                    lazy(
                                                        () =>
                                                            import('@/components/admin/users/settings/GeneralContainer')
                                                    )
                                                ),
                                            },
                                        ],
                                    },
                                    {
                                        path: 'servers',
                                        element: lazyLoad(
                                            lazy(() => import('@/components/admin/users/servers/UserServersContainer'))
                                        ),
                                    },
                                ],
                            },
                        ],
                    },
                    {
                        path: 'tokens',
                        element: lazyLoad(lazy(() => import('@/components/admin/tokens/TokensContainer'))),
                    },
                ],
            },
        ],
    },
    {
        path: '*',
        element: <NotFound full />,
    },
])

export default router
