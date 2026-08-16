import { createLazyFileRoute } from '@tanstack/react-router'

import FirewallLogCard from '@/features/servers/firewall/components/FirewallLogCard.tsx'
import FirewallOptionsCard from '@/features/servers/firewall/components/FirewallOptionsCard.tsx'
import RulesCard from '@/features/servers/firewall/components/RulesCard.tsx'

import Heading from '@/components/ui/Typography/Heading.tsx'

/**
 * Stacked full-width rather than a card grid: the rules table needs the
 * horizontal room, and pairing it with the short options card in a row would
 * stretch the latter to match for nothing.
 */
const ServerFirewall = () => {
    const { serverUuid } = Route.useParams()

    return (
        <>
            <Heading>Firewall</Heading>
            <div className={'flex flex-col gap-4'}>
                <FirewallOptionsCard uuid={serverUuid} />
                <RulesCard uuid={serverUuid} />
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
