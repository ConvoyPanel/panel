import useTitle from '@/hooks/use-title.ts'
import { IconServer, IconLock } from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import AppLayout from '@/components/layouts/AppLayout.tsx'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'

export const Route = createFileRoute('/_app/_dashboard')({
    component: () => {
        useTitle()

        return (
            <AppLayout routes={nav}>
                <Outlet />
            </AppLayout>
        )
    },
    staticData: {
        title: 'Dashboard',
    },
})

const nav: SidebarNav = {
    key: 'client',
    groups: [
        {
            items: [
                {
                    icon: IconServer,
                    label: 'Servers',
                    path: '/',
                    activeOptions: { exact: true },
                },
                {
                    icon: IconLock,
                    label: 'Security',
                    path: '/security',
                },
            ],
        },
    ],
}
