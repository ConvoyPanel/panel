import ServerOverviewContainer from '@/components/servers/overview/ServerOverviewContainer'
import ServerTerminalContainer from '@/components/servers/terminal/ServerTerminalContainer'

interface RouteDefinition {
  path: string
  name: string
  component: React.ComponentType
}

interface Routes {
  // All of the routes available under "/servers/:id"
  server: RouteDefinition[]
}

export default {
  server: [
    {
      path: '/',
      name: 'Overview',
      component: ServerOverviewContainer,
    },
    {
      path: '/terminal',
      name: 'Terminal',
      component: ServerTerminalContainer
    },
  ],
} as Routes
