import { AuthenticatedUser } from '@/types/user.ts'
import { Outlet, createFileRoute, redirect } from '@tanstack/react-router'
import { Cache, cache as SWRCache } from 'swr/_internal'

import { cacheUser, getKey } from '@/api/auth/use-user-swr.ts'

import LargeSidebar from '@/components/ui/Navigation/LargeSidebar.tsx'


export const Route = createFileRoute('/_app')({
    beforeLoad: async ({ location }) => {
        const cache: Cache<AuthenticatedUser> = SWRCache

        await cacheUser().catch(_ => {})

        if (cache.get(getKey())?.data === undefined) {
            throw redirect({
                to: '/auth/login',
                search: {
                    redirect: location.href,
                },
            })
        }
    },
    component: AppLayout,
})

function AppLayout() {
    return (
        <div className={'flex h-full'}>
            <LargeSidebar />
            <div className={'flex flex-1 flex-col'}>
                <div
                    className={
                        'flex h-12 max-h-12 items-center justify-between py-2 px-5 border-b border-default'
                    }
                >
                    <p>hi</p>
                </div>
                <main className={'flex-1 flex-grow overflow-y-auto'}>
                    <Outlet />
                </main>
            </div>
        </div>
    )
}
