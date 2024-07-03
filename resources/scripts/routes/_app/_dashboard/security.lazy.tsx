import { createLazyFileRoute } from '@tanstack/react-router'

import AuthenticationCard from '@/components/interfaces/Client/Security/AuthenticationCard.tsx'
import KeychainCard from '@/components/interfaces/Client/Security/KeychainCard.tsx'
import SessionListCard from '@/components/interfaces/Client/Security/SessionListCard.tsx'

import { Heading } from '@/components/ui/Typography'


export const Route = createLazyFileRoute('/_app/_dashboard/security')({
    component: () => (
        <>
            <Heading>Security</Heading>
            <div className={'grid grid-cols-1 gap-5 md:grid-cols-2'}>
                <KeychainCard />
                <AuthenticationCard />
                <SessionListCard />
            </div>
        </>
    ),
    // @ts-expect-error
    meta: () => [{ title: 'Security' }],
})
