import { AuthenticatedUser } from '@/types/user.ts'
import { createFileRoute, redirect } from '@tanstack/react-router'
import { z } from 'zod'

import { cacheUser, getKey } from '@/api/auth/use-user-swr.ts'
import { queryClient } from '@/lib/query-client.ts'

const searchSchema = z.object({
    query: z.string().optional(),
    page: z.number().optional(),
    perPage: z.number().optional(),
})

export const Route = createFileRoute('/_app')({
    validateSearch: searchSchema,
    beforeLoad: async ({ location }) => {
        await cacheUser().catch(_ => {})

        const user = queryClient.getQueryData<AuthenticatedUser>([getKey()])

        if (!user) {
            throw redirect({
                to: '/auth/login',
                search: {
                    redirect: location.href !== '/' ? location.href : undefined,
                },
            })
        }
    },
})
