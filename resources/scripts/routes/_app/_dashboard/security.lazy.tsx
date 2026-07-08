import { createLazyFileRoute } from '@tanstack/react-router'

import ApiKeysCard from '@/features/account/components/ApiKeysCard.tsx'
import AuthenticationCard from '@/features/account/components/AuthenticationCard.tsx'
import KeychainCard from '@/features/account/components/KeychainCard.tsx'
import SessionListCard from '@/features/account/components/SessionListCard.tsx'

import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/_dashboard/security')({
    component: () => (
        <>
            <Heading>Security</Heading>
            <div className={'grid grid-cols-1 gap-5 @md:grid-cols-2'}>
                <KeychainCard />
                <AuthenticationCard />
                <ApiKeysCard />
                <SessionListCard />
            </div>
        </>
    ),
})
