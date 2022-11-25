import { lazy } from 'react'

interface RouteDefinition {
  path: string
  name: string
  component: React.ComponentType
}

interface Routes {
  // All of the routes available under "/servers/:id"
  server: RouteDefinition[]
}

/*
|--------------------------------------------------------------------------
| Server Routes
|--------------------------------------------------------------------------
|
| Route: /servers/<id>
|
*/

export default {
  server: [
    {
      path: '/',
      name: 'Overview',
      component: lazy(() => import('@/components/servers/overview/ServerOverviewContainer')),
    },
    {
      path: '/backups',
      name: 'Backups',
      component: lazy(() => import('@/components/servers/backups/BackupsContainer')),
    },
    {
      path: '/settings',
      name: 'Settings',
      component: lazy(() => import('@/components/servers/settings/ServerSettingsContainer')),
    },
    {
      path: '/terminal',
      name: 'Terminal',
      component: lazy(() => import('@/components/servers/terminal/ServerTerminalContainer')),
    },
  ],
} as Routes
