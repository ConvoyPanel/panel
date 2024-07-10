import { AuthenticatedUser } from '@/types/user.ts'
import { Outlet, createFileRoute, redirect } from '@tanstack/react-router'
import { Cache, cache as SWRCache } from 'swr/_internal'

import { cacheUser, getKey } from '@/api/auth/use-user-swr.ts'

import LogoWithName from '@/components/ui/Branding/LogoWithName.tsx'
import { Card } from '@/components/ui/Card'
import ThemeToggle from '@/components/ui/ThemeToggle.tsx'


export const Route = createFileRoute('/auth')({
    beforeLoad: async () => {
        // I don't like how I'm accessing internal functions here. Rewrite this if this portion ever needs to be edited
        const cache: Cache<AuthenticatedUser> = SWRCache

        await cacheUser().catch(_ => {})

        if (cache.get(getKey())?.data !== undefined) {
            throw redirect({
                to: '/',
            })
        }
    },
    component: AuthLayout,
    notFoundComponent: NotFound,
})

function AuthLayout() {
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
