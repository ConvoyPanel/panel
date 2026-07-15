import useTitle from '@/hooks/use-title.ts'
import {
    IconBoxMargin,
    IconHome,
    IconKey,
    IconMapPin,
    IconMapPins,
    IconServer,
    IconSettings,
} from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import AppLayout from '@/components/layouts/AppLayout.tsx'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'

export const Route = createFileRoute('/_app/admin/_dashboard')({
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
    key: 'admin',
    groups: [
        {
            items: [
                {
                    icon: IconHome,
                    label: 'Dashboard',
                    path: '/admin',
                    activeOptions: {
                        exact: true,
                    },
                },
            ],
        },
        {
            label: 'Infrastructure',
            items: [
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
            ],
        },
        {
            label: 'Provisioning',
            items: [
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
            ],
        },
        {
            label: 'Administration',
            items: [
                {
                    icon: IconKey,
                    label: 'API Tokens',
                    path: '/admin/tokens',
                },
                {
                    icon: IconSettings,
                    label: 'Settings',
                    path: '/admin/settings',
                },
            ],
        },
    ],
}
