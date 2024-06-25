import { AuthenticatedUser } from '@/types/user.ts'
import { createFileRoute, redirect } from '@tanstack/react-router'
import { Cache, cache as SWRCache } from 'swr/_internal'
import { z } from 'zod'

import { cacheUser, getKey } from '@/api/auth/use-user-swr.ts'


const searchSchema = z.object({
    page: z.number().catch(1),
})

export const Route = createFileRoute('/_app')({
    validateSearch: searchSchema,
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
})
