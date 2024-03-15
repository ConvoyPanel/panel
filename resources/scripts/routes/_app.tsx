import { AuthenticatedUser } from '@/types/user.ts'
import { createFileRoute, redirect } from '@tanstack/react-router'
import { Cache, cache as SWRCache } from 'swr/_internal'

import { cacheUser, getKey } from '@/api/transformers/use-user-swr.ts'


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
})
