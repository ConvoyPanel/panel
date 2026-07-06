import useTitle from '@/hooks/use-title.ts'
import { AuthenticatedUser } from '@/types/user.ts'
import { Outlet, createFileRoute, redirect } from '@tanstack/react-router'

import { cacheUser, currentUserQueries } from '@/features/auth/api.ts'
import { queryClient } from '@/lib/query-client.ts'

import LogoWithName from '@/components/ui/Branding/LogoWithName.tsx'
import { Card } from '@/components/ui/Card'
import ThemeToggle from '@/components/ui/ThemeToggle.tsx'

export const Route = createFileRoute('/auth')({
    beforeLoad: async () => {
        await cacheUser().catch(_ => {})

        const user = queryClient.getQueryData<AuthenticatedUser>(currentUserQueries.all())

        if (user !== undefined) {
            throw redirect({ to: '/' })
        }
    },
    component: AuthLayout,
    notFoundComponent: NotFound,
})

function AuthLayout() {
    useTitle()

    return (
        <div className={'flex h-full justify-center px-4'}>
            <div className={'w-full pt-6 sm:w-96 md:pt-14 lg:w-[30rem]'}>
                <LogoWithName className={'pb-6'} />
                <Card>
                    <Outlet />
                </Card>
                <div className={'flex justify-end pt-3'}>
                    <ThemeToggle variant={'ghost'} />
                </div>
            </div>
        </div>
    )
}

function NotFound() {
    return (
        <div className={'p-6'}>
            <h1 className={'text-xl font-semibold'}>404</h1>
            <p>Incorrect login flow</p>
        </div>
    )
}
