import ServerOverviewContainer from '@/components/servers/overview/ServerOverviewContainer'

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
  ],
} as Routes
