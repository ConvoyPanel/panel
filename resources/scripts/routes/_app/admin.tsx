import { AuthenticatedUser } from '@/types/user.ts'
import { createFileRoute, redirect } from '@tanstack/react-router'

import { getKey } from '@/api/auth/use-user-swr.ts'
import { queryClient } from '@/lib/query-client.ts'

export const Route = createFileRoute('/_app/admin')({
    beforeLoad: () => {
        const user = queryClient.getQueryData<AuthenticatedUser>([getKey()])

        if (user?.rootAdmin !== true) {
            throw redirect({ to: '/' })
        }
    },
})
