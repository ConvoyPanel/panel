import useTitle from '@/hooks/use-title.ts'
import { IconServer, IconShieldLock, IconUser } from '@tabler/icons-react'
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
            ],
        },
        /*
         * The account's own settings, in the sidebar rather than behind a
         * drilled-in section of their own. They are the only other place a
         * client goes, and grouping them here is what keeps a "Profile" item
         * from reading as a sibling of "Servers" — the two are different kinds
         * of thing, and the group label is what says so.
         *
         * The admin console reaches the same pages through the avatar menu,
         * which is the one control both workspaces share.
         */
        {
            label: 'Account',
            items: [
                {
                    icon: IconUser,
                    label: 'Profile',
                    path: '/account',
                    activeOptions: { exact: true },
                },
                {
                    icon: IconShieldLock,
                    label: 'Security',
                    path: '/account/security',
                },
            ],
        },
    ],
}
