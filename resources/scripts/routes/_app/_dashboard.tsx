import useTitle from '@/hooks/use-title.ts'
import { IconHome, IconLock } from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import AppLayout from '@/components/layouts/AppLayout.tsx'

import { Route as RouteDef } from '@/components/ui/Navigation/Navigation.types.ts'


export const Route = createFileRoute('/_app/_dashboard')({
    component: () => {
        useTitle()

        return (
            <AppLayout routes={routes}>
                <Outlet />
            </AppLayout>
        )
    },
    meta: () => [{ title: 'Dashboard' }],
})

const routes: RouteDef[] = [
    {
        icon: IconHome,
        label: 'Dashboard',
        path: '/',
    },
    {
        icon: IconLock,
        label: 'Security',
        path: '/security',
    },
]
