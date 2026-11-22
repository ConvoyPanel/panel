import {
    passwordResetsEnabled,
    resetLinkQuery,
} from '@/features/auth/password-reset.ts'
import { createFileRoute, notFound } from '@tanstack/react-router'
import { z } from 'zod'

import { queryClient } from '@/lib/query-client.ts'

export const searchSchema = z.object({
    email: z.string().catch(''),
})

export const Route = createFileRoute(
    '/auth/(password-reset)/reset-password/$token'
)({
    validateSearch: searchSchema,
    beforeLoad: () => {
        if (!passwordResetsEnabled()) throw notFound()
    },
    loaderDeps: ({ search }) => ({ email: search.email }),
    // Warmed, not required, for the same reason as the invite route: a dead
    // link has its own screen, and a rejected loader would replace it with the
    // router's generic error.
    loader: ({ params, deps }) =>
        queryClient
            .ensureQueryData(resetLinkQuery(params.token, deps.email))
            .catch(() => null),
    staticData: {
        title: 'Reset password',
    },
})
