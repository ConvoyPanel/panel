import { createLazyFileRoute } from '@tanstack/react-router'

import PasswordCard from '@/features/servers/security/components/PasswordCard.tsx'
import SSHKeysCard from '@/features/servers/security/components/SSHKeysCard.tsx'

import Heading from '@/components/ui/Typography/Heading.tsx'

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/security')({
    component: ServerSecurity,
    // @ts-ignore
    meta: () => [{ title: 'Security' }],
})

function ServerSecurity() {
    const { serverUuid } = Route.useParams()

    return (
        <>
            <Heading>Security</Heading>
            <div className={'grid grid-cols-1 gap-5 @md:grid-cols-2'}>
                <SSHKeysCard uuid={serverUuid} />
                <PasswordCard uuid={serverUuid} />
            </div>
        </>
    )
}
