import { useUser } from '@/features/auth/api.ts'
import useTitle from '@/hooks/use-title.ts'
import { IconShieldLock, IconUser } from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import AppLayout from '@/components/layouts/AppLayout.tsx'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'

const AccountLayout = () => {
    const { data: user } = useUser()
    useTitle('Account')

    const nav: SidebarNav = {
        key: 'account',
        back: user?.rootAdmin
            ? { label: 'Admin', to: '/admin' }
            : { label: 'Servers', to: '/' },
        context: {
            title: user?.name ?? 'Account',
            subtitle: user?.email,
            icon: IconUser,
        },
        groups: [
            {
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

    return (
        <AppLayout routes={nav}>
            <Outlet />
        </AppLayout>
    )
}

export const Route = createFileRoute('/_app/account')({
    component: AccountLayout,
    staticData: {
        title: 'Account',
    },
})

/**
 * The account's own settings, drilled in from the avatar menu the way
 * `/admin/settings` drills in from the admin console.
 *
 * Deliberately not an item in either sidebar: the account is the same account
 * in the client area and the admin console, so a link in one of them is either
 * missing from the other or duplicated across both. Security used to sit in the
 * client sidebar and was unreachable from the admin console for exactly that
 * reason.
 */
