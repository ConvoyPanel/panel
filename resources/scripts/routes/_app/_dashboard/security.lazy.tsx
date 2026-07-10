import { createLazyFileRoute } from '@tanstack/react-router'
import { useEffect } from 'react'
import { toast } from 'sonner'

import { oauthErrorMessage } from '@/features/auth/oauth.ts'

import ApiKeysCard from '@/features/account/components/ApiKeysCard.tsx'
import AuthenticationCard from '@/features/account/components/AuthenticationCard.tsx'
import KeychainCard from '@/features/account/components/KeychainCard.tsx'
import OAuthConnectionsCard from '@/features/account/components/OAuthConnectionsCard.tsx'
import SessionListCard from '@/features/account/components/SessionListCard.tsx'

import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/_dashboard/security')({
    component: Security,
})

function Security() {
    const { oauth_linked: oauthLinked, oauth_error: oauthError } =
        Route.useSearch()
    const navigate = Route.useNavigate()

    useEffect(() => {
        if (!oauthLinked && !oauthError) return

        if (oauthError) {
            toast.error(oauthErrorMessage(oauthError))
        } else if (oauthLinked) {
            toast.success('Provider connected')
        }

        // Strip the one-shot params so a refresh doesn't re-toast.
        void navigate({ to: '/security', search: {}, replace: true })
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [oauthLinked, oauthError])

    return (
        <>
            <Heading>Security</Heading>
            <div className={'grid grid-cols-1 gap-5 @md:grid-cols-2'}>
                <KeychainCard />
                <AuthenticationCard />
                <ApiKeysCard />
                <SessionListCard />
                <OAuthConnectionsCard />
            </div>
        </>
    )
}
