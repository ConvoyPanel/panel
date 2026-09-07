import { inviteQuery } from '@/features/auth/invites.ts'
import { createFileRoute } from '@tanstack/react-router'

import { queryClient } from '@/lib/query-client.ts'

export const Route = createFileRoute('/auth/(invite)/invite/$token')({
    /*
     * Warmed, not required. `ensureQueryData` rejects on a 404, and an unhandled rejection here
     * is caught by the router's error boundary, which replaces the whole screen with a generic
     * "Something went wrong / Retry" — the one thing a dead invite must not show, since there is
     * nothing to retry and the component has a proper explanation for exactly this case.
     * Swallowing it lets the route mount and the component read the same failed query.
     */
    loader: ({ params }) =>
        queryClient
            .ensureQueryData(inviteQuery(params.token))
            .catch(() => null),
    staticData: {
        title: 'Set your password',
    },
})
