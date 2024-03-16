import { AuthenticatedUser } from '@/types/user.ts'
import { Outlet, createFileRoute, redirect } from '@tanstack/react-router'
import { Cache, cache as SWRCache } from 'swr/_internal'

import { cacheUser, getKey } from '@/api/transformers/use-user-swr.ts'

import { Card } from '@/components/ui/Card'
import Logo from '@/components/ui/Logo.tsx'
import ThemeToggle from '@/components/ui/ThemeToggle.tsx'


export const Route = createFileRoute('/auth')({
    beforeLoad: async () => {
        const cache: Cache<AuthenticatedUser> = SWRCache

        await cacheUser().catch(_ => {})

        if (cache.get(getKey())?.data !== undefined) {
            throw redirect({
                to: '/',
            })
        }
    },
    component: AuthLayout,
})

function AuthLayout() {
    return (
        <div className={'flex justify-center h-full px-4'}>
            <div className={'w-full sm:w-96 lg:w-[30rem] pt-6 md:pt-14'}>
                <div className={'flex items-center pb-6'}>
                    <Logo className={'text-foreground h-6 w-6 mr-2'} />
                    <p className={'text-xl font-black'}>Convoy</p>
                </div>
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
