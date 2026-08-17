import useTitle from '@/hooks/use-title.ts'
import {
    IconAnchor,
    IconGauge,
    IconRefresh,
    IconSettings,
} from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import AppLayout from '@/components/layouts/AppLayout.tsx'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'

export const Route = createFileRoute('/_app/admin/settings')({
    component: SettingsLayout,
    staticData: {
        title: 'Settings',
    },
})

// One route per section, drilled in from the admin console. Only add an item
// here once its route file exists — a nav that points at nothing is how the
// client server tabs ended up declared-but-missing.
const nav: SidebarNav = {
    key: 'admin-settings',
    back: { label: 'Admin', to: '/admin' },
    context: {
        title: 'Settings',
        subtitle: 'Panel-wide defaults',
        icon: IconSettings,
    },
    groups: [
        {
            items: [
                {
                    icon: IconGauge,
                    label: 'Bandwidth',
                    path: '/admin/settings/bandwidth',
                },
                {
                    icon: IconAnchor,
                    label: 'Anchor',
                    path: '/admin/settings/anchor',
                },
                {
                    icon: IconRefresh,
                    label: 'Updates',
                    path: '/admin/settings/updates',
                },
            ],
        },
    ],
}

function SettingsLayout() {
    useTitle('Settings')

    return (
        <AppLayout routes={nav}>
            <Outlet />
        </AppLayout>
    )
}
