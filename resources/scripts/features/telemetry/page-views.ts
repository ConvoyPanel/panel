import { sendPageViews } from '@/features/telemetry/api.ts'
import type { AnyRouter } from '@tanstack/react-router'

import type { PageViews } from './api.ts'

const FLUSH_INTERVAL_MS = 5 * 60_000

/**
 * Counts page views for the telemetry report (docs/telemetry.md).
 *
 * The panel is a client-side app, so the server never sees a page change. This
 * counts each navigation by route id (`/_app/servers/$serverUuid/backups`,
 * never the address with the server in it) and sends the counts every five
 * minutes and when the tab is hidden. A search-param change on the same page,
 * like paging a table, is not a view, and neither is a hover prefetch.
 *
 * Does nothing at all when telemetry is off.
 */
const trackPageViews = (router: AnyRouter) => {
    if (!window.SiteConfiguration?.telemetry) return

    let pending: PageViews = {}

    const flush = () => {
        if (Object.keys(pending).length === 0) return

        const views = pending
        pending = {}

        // A batch that fails is dropped, not retried: lost counts are an undercount, not an
        // error worth showing anyone.
        sendPageViews(views).catch(() => {})
    }

    let lastPathname: string | undefined

    // `onRendered` rather than `onResolved`: the latter only fires when a navigation went through
    // a pending state, so a page whose data was already preloaded on hover was never counted. It
    // also fires when only the search params change, which the pathname check skips.
    router.subscribe('onRendered', ({ toLocation }) => {
        if (toLocation.pathname === lastPathname) return

        lastPathname = toLocation.pathname

        // Resolved from the path, not from the rendered matches, which can still be the previous
        // page's while the next one is pending.
        const routeId = router.getMatchedRoutes(toLocation.pathname).foundRoute
            ?.id

        // Only the signed-in app: the sign-in screens are not usage.
        if (!routeId?.startsWith('/_app/')) return

        pending[routeId] = (pending[routeId] ?? 0) + 1
    })

    window.setInterval(flush, FLUSH_INTERVAL_MS)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') flush()
    })
}

export default trackPageViews
