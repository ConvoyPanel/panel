import { ComponentType, FC, FunctionComponent, lazy, LazyExoticComponent, ReactNode } from 'react'
import { ServerContext } from '@/state/server'
import Spinner from '@/components/elements/Spinner'
import ServerOverviewContainer from '@/components/servers/overview/ServerOverviewContainer'
import { createBrowserRouter, Outlet } from 'react-router-dom'
import AuthenticatedRoutes from '@/routers/middleware/AuthenticatedRoutes'
import { NotFound } from '@/components/elements/ScreenBlock'
import { NodeContext } from '@/state/admin/node'
import { AdminServerContext } from '@/state/admin/server'
import { AdminUserContext } from '@/state/admin/user'

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
        path: '/',
        element: (
            <AuthenticatedRoutes>
                <Outlet />
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
                        {lazyLoad(lazy(() => import('@/routers/admin/AdminDashboardRouter')))}
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
                                        {lazyLoad(lazy(() => import('@/routers/admin/NodeRouter')))}
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
                                        {lazyLoad(lazy(() => import('@/routers/admin/AdminServerRouter')))}
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
                                        {lazyLoad(lazy(() => import('./admin/AdminUserRouter')))}
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
