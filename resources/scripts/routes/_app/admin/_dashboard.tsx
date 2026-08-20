import useTitle from '@/hooks/use-title.ts'
import {
    IconBoxMargin,
    IconDatabase,
    IconHistory,
    IconHome,
    IconKey,
    IconMapPin,
    IconMapPins,
    IconServer,
    IconSettings,
    IconStack2,
    IconTransform,
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
                    icon: IconTransform,
                    label: 'Anchors',
                    path: '/admin/anchors',
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
                    icon: IconDatabase,
                    label: 'Storage',
                    path: '/admin/storage',
                },
                {
                    icon: IconBoxMargin,
                    label: 'Templates',
                    path: '/admin/templates',
                },
                {
                    icon: IconStack2,
                    label: 'Server Presets',
                    path: '/admin/server-presets',
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
                    icon: IconHistory,
                    label: 'Audit Log',
                    path: '/admin/audit-log',
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
