import ConsoleView from '@/features/servers/console/ConsoleView'
import { createLazyFileRoute } from '@tanstack/react-router'

export const Route = createLazyFileRoute(
    '/_app/(fullscreen)/servers/$serverUuid/console'
)({ component: () => <ConsolePage /> })

// The console owns its own chrome: the toolbar has to sit in the header rather
// than over the guest, and it needs the live connection to drive, so the header
// and the connection cannot live in separate components.
const ConsolePage = () => {
    const { serverUuid } = Route.useParams()
    const { type } = Route.useSearch()

    return <ConsoleView serverUuid={serverUuid} type={type} />
}
