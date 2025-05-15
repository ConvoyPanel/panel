import useTitle from '@/hooks/use-title.ts'
import {
  IconBoxMargin,
  IconHome,
  IconMapPin,
  IconMapPins,
  IconServer,
} from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import AppLayout from '@/components/layouts/AppLayout.tsx'

import { Route as RouteDef } from '@/components/ui/Navigation/Navigation.types.ts'

export const Route = createFileRoute('/_app/admin/(dashboard)')({
  component: () => {
    useTitle()

    return (
      <AppLayout routes={routes}>
        <Outlet />
      </AppLayout>
    )
  },
  staticData: {
    title: 'Dashboard',
  },
})

const routes: RouteDef[] = [
  {
    icon: IconHome,
    label: 'Dashboard',
    path: '/admin',
    activeOptions: {
      exact: true,
    },
  },
  {
    icon: IconMapPin,
    label: 'Locations',
    path: '/admin/locations',
  },
  {
    icon: IconServer,
    label: 'Nodes',
    path: '/admin/nodes',
  },
  {
    icon: IconServer,
    label: 'Servers',
    path: '/admin/servers',
  },
  {
    icon: IconMapPins,
    label: 'IPAM',
    path: '/admin/ipam',
  },
  {
    icon: IconBoxMargin,
    label: 'Templates',
    path: '/admin/templates',
  },
]
