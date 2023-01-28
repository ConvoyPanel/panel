import { ComponentType, FC, FunctionComponent, lazy, LazyExoticComponent, ReactNode } from 'react'
import ServerRouter from './ServerRouter'
import DashboardRouter from '@/routers/DashboardRouter'
import { ServerContext } from '@/state/server'
import Spinner from '@/components/elements/Spinner'
import ServerOverviewContainer from '@/components/servers/overview/ServerOverviewContainer'

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

const routes: Route[] = [
    {
        path: '/',
        component: DashboardRouter,
    },
    {
        path: '/servers/:id/*',
        component: ({ children }) => (
            <ServerContext.Provider>
                <Spinner.Suspense>
                    <ServerRouter />
                </Spinner.Suspense>
            </ServerContext.Provider>
        ),
        children: [
            {
                path: '/servers/:id/*',
                component: () => (
                    <ServerContext.Provider>
                        <Spinner.Suspense>
                            <ServerRouter>
                                <ServerOverviewContainer />
                            </ServerRouter>
                        </Spinner.Suspense>
                    </ServerContext.Provider>
                ) /*lazy(() => import('@/components/servers/overview/ServerOverviewContainer')),*/,
            },
            {
                path: '/backups',
                component: lazy(() => import('@/components/servers/backups/BackupsContainer')),
            },
            {
                path: '/settings',
                component: lazy(() => import('@/components/servers/settings/ServerSettingsContainer')),
            },
            {
                path: '/terminal',
                component: lazy(() => import('@/components/servers/terminal/ServerTerminalContainer')),
            },
        ],
    },
]

const oldroutes = {
    server: [
        {
            path: '/',
            component: lazy(() => import('@/components/servers/overview/ServerOverviewContainer')),
        },
        {
            path: '/backups',
            component: lazy(() => import('@/components/servers/backups/BackupsContainer')),
        },
        {
            path: '/settings',
            component: lazy(() => import('@/components/servers/settings/ServerSettingsContainer')),
        },
        {
            path: '/terminal',
            component: lazy(() => import('@/components/servers/terminal/ServerTerminalContainer')),
        },
    ],
    admin: {
        dashboard: [
            {
                path: '/',
                component: lazy(() => import('@/components/admin/overview/OverviewContainer')),
            },
            {
                path: '/locations',
                component: lazy(() => import('@/components/admin/locations/LocationsContainer')),
            },
            {
                path: '/nodes',
                component: lazy(() => import('@/components/admin/nodes/NodesContainer')),
            },
            {
                path: '/servers',
                component: lazy(() => import('@/components/admin/servers/ServersContainer')),
            },
            {
                path: '/users',
                component: lazy(() => import('@/components/admin/users/UsersContainer')),
            },
            {
                path: '/tokens',
                component: lazy(() => import('@/components/admin/tokens/TokensContainer')),
            },
        ],
        node: [
            {
                path: '/',
                component: lazy(() => import('@/components/admin/nodes/overview/NodeOverviewContainer')),
            },
            {
                path: '/servers',
                component: lazy(() => import('@/components/admin/nodes/servers/NodeServersContainer')),
            },
            {
                path: '/isos',
                component: lazy(() => import('@/components/admin/nodes/isoLibrary/IsoLibraryContainer')),
            },
            {
                path: '/templates',
                component: lazy(() => import('@/components/admin/nodes/templates/NodeTemplatesContainer')),
            },
            {
                path: '/addresses',
                component: lazy(() => import('@/components/admin/nodes/addresses/NodeAddressesContainer')),
            },
            {
                path: '/settings',
                component: lazy(() => import('@/components/admin/nodes/settings/NodeSettingsContainer')),
            },
        ],
        server: [
            {
                path: '/',
                component: lazy(() => import('@/components/admin/servers/overview/ServerOverviewContainer')),
            },
            {
                path: '/settings',
                component: lazy(() => import('@/components/admin/servers/settings/ServerSettingsContainer')),
            },
        ],
        user: [
            {
                path: '/settings',
                component: lazy(() => import('@/components/admin/users/settings/UserSettingsContainer')),
            },
            {
                path: '/servers',
                component: lazy(() => import('@/components/admin/users/servers/UserServersContainer')),
            },
        ],
    },
}

export default routes
