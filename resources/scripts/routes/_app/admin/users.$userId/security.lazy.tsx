import OAuthConnectionsCard from '@/features/users/components/Security/OAuthConnectionsCard.tsx'
import PasskeysCard from '@/features/users/components/Security/PasskeysCard.tsx'
import SSHKeysCard from '@/features/users/components/Security/SSHKeysCard.tsx'
import TwoFactorCard from '@/features/users/components/Security/TwoFactorCard.tsx'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/users/$userId/security')({
    component: UserSecurity,
})

/**
 * The same card grid as the account's own `/security`, minus everything that mints a credential:
 * an admin can see what can sign in as this account and take any of it away, but cannot add to it.
 */
function UserSecurity() {
    const { userId } = Route.useParams()
    const numericUserId = Number(userId)

    return (
        <>
            <Heading>Security</Heading>
            <div className={'grid grid-cols-1 gap-4 @3xl:grid-cols-2'}>
                <TwoFactorCard userId={numericUserId} />
                <PasskeysCard userId={numericUserId} />
                <SSHKeysCard userId={numericUserId} />
                <OAuthConnectionsCard userId={numericUserId} />
            </div>
        </>
    )
}
