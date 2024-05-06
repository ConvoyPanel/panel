import { IconHome } from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import AppLayout from '@/components/layouts/AppLayout.tsx'

import { Route as RouteDef } from '@/components/ui/Navigation/Navigation.types.ts'


export const Route = createFileRoute('/_app/_dashboard')({
    component: () => (
        <AppLayout routes={routes}>
            <Outlet />
        </AppLayout>
    ),
})

const routes: RouteDef[] = [
    {
        icon: IconHome,
        label: 'Dashboard',
        path: Route.path,
    },
    {
        icon: IconHome,
        label: 'Test',
        path: '/test',
    },
]
