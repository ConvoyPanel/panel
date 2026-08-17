import { useFirewallOptions } from '@/features/servers/firewall/api.ts'
import FirewallLedger from '@/features/servers/firewall/components/FirewallLedger.tsx'
import FirewallLogCard from '@/features/servers/firewall/components/FirewallLogCard.tsx'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Badge } from '@/components/ui/Badge.tsx'
import Heading from '@/components/ui/Typography/Heading.tsx'

const ServerFirewall = () => {
    const { serverUuid } = Route.useParams()
    // A cache read; the ledger below owns this query.
    const { data: options } = useFirewallOptions(serverUuid)

    return (
        <>
            <div
                className={
                    'flex flex-wrap items-start justify-between gap-x-4 gap-y-2'
                }
            >
                <div className={'min-w-0'}>
                    <Heading>Firewall</Heading>
                    <p className={'mt-1 text-sm text-muted-foreground'}>
                        Control the traffic allowed in and out of this server.
                    </p>
                </div>

                {options && !options.isEnabled && (
                    <Badge variant={'secondary'} className={'mt-1.5'}>
                        Not enforced
                    </Badge>
                )}
            </div>

            <div className={'flex flex-col gap-4'}>
                <FirewallLedger uuid={serverUuid} />
                <FirewallLogCard uuid={serverUuid} />
            </div>
        </>
    )
}

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/firewall')({
    component: ServerFirewall,
    // @ts-ignore
    meta: () => [{ title: 'Firewall' }],
})
