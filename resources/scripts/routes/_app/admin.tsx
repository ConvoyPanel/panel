import { AuthenticatedUser } from '@/types/user.ts'
import { createFileRoute, redirect } from '@tanstack/react-router'

import { currentUserQueries } from '@/features/auth/api.ts'
import { queryClient } from '@/lib/query-client.ts'

export const Route = createFileRoute('/_app/admin')({
    beforeLoad: () => {
        const user = queryClient.getQueryData<AuthenticatedUser>(currentUserQueries.all())

        if (user?.rootAdmin !== true) {
            throw redirect({ to: '/' })
        }
    },
})
