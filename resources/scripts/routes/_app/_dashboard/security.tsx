import { searchSchema } from '@/routes/_app/_dashboard/account/security.tsx'
import { createFileRoute, redirect } from '@tanstack/react-router'

/**
 * Security moved into `/account`. Kept so a bookmark, or a link from before the
 * move, still lands on the page rather than on the router's not-found — search
 * included, since the OAuth callback used to come back through here.
 */
export const Route = createFileRoute('/_app/_dashboard/security')({
    validateSearch: searchSchema,
    beforeLoad: ({ search }) => {
        throw redirect({ to: '/account/security', search, replace: true })
    },
})
