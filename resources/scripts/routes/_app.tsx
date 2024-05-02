import { AuthenticatedUser } from '@/types/user.ts'
import { Outlet, createFileRoute, redirect } from '@tanstack/react-router'
import { Cache, cache as SWRCache } from 'swr/_internal'

import { cacheUser, getKey } from '@/api/auth/use-user-swr.ts'

import Sidebar from '@/components/ui/Navigation/Sidebar.tsx'


export const Route = createFileRoute('/_app')({
    beforeLoad: async ({ location }) => {
        const cache: Cache<AuthenticatedUser> = SWRCache

        await cacheUser().catch(_ => {})

        if (cache.get(getKey())?.data === undefined) {
            throw redirect({
                to: '/auth/login',
                search: {
                    redirect: location.href !== '/' ? location.href : undefined,
                },
            })
        }
    },
    component: AppLayout,
})

function AppLayout() {
    return (
        <div className='flex min-h-screen w-full flex-col bg-muted/40'>
            <Sidebar />
            <div className='flex flex-col sm:gap-4 sm:py-4 sm:pl-14'>
                <main className={'p-4 sm:px-6 sm:py-0'}>
                    <Outlet />
                </main>
            </div>
        </div>
    )
}
