import { lazy } from 'react'

interface RouteDefinition {
    path: string
    component: React.ComponentType
}

interface Routes {
    // All of the routes available under "/servers/:id"
    server: RouteDefinition[]
    admin: {
        dashboard: RouteDefinition[]
        node: RouteDefinition[]
        server: RouteDefinition[]
    }
}

/*
|--------------------------------------------------------------------------
| Server Routes
|--------------------------------------------------------------------------
|
| Route: /servers/<id>
|
*/

const routes: Routes = {
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
    },
}

export default routes
