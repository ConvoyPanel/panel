import { AuthenticatedUser } from '@/types/user.ts'
import { createFileRoute, redirect } from '@tanstack/react-router'

import { currentUserQueries } from '@/features/auth/api.ts'
import { queryClient } from '@/lib/query-client.ts'

export const Route = createFileRoute('/_app/admin')({
    beforeLoad: () => {
        const user = queryClient.getQueryData<AuthenticatedUser>(currentUserQueries.all())

        // Any admin role opens the door; which sections are behind it is the sidebar's and the
        // API's business. Gating this on `rootAdmin` would have sent every narrower role
        // straight back to the client area.
        if ((user?.adminPermissions?.length ?? 0) === 0) {
            throw redirect({ to: '/' })
        }
    },
})
