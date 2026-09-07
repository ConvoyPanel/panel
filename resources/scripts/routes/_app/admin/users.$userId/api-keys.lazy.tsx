import UserApiKeysCard from '@/features/users/components/Security/UserApiKeysCard.tsx'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/users/$userId/api-keys')({
    component: UserApiKeys,
})

function UserApiKeys() {
    const { userId } = Route.useParams()

    return (
        <>
            <Heading>API keys</Heading>
            {/* One card, capped: a full-width table of two tokens reads as a page that failed to
                load. The cap matches the rebuild page's `max-w-3xl` form column. */}
            <div className={'w-full max-w-3xl'}>
                <UserApiKeysCard userId={Number(userId)} />
            </div>
        </>
    )
}
