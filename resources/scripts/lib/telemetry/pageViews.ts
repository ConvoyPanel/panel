import type { createBrowserRouter } from 'react-router-dom'

type Router = ReturnType<typeof createBrowserRouter>
type RouterState = Router['state']

/** Route pattern => times it was opened. Nothing else goes in the body. */
export type PageViews = Record<string, number>

const FLUSH_INTERVAL_MS = 5 * 60_000

/**
 * The page's route pattern: the matched routes' paths joined, so
 * `/admin/nodes/:nodeId/templates` and never the address with the node's id in
 * it. The server only counts patterns it knows (PageKeys.php) and names the
 * page itself, so nothing typed into the address bar reaches the report.
 */
export const routePattern = (state: RouterState): string => {
    let pattern = ''

    for (const match of state.matches) {
        const path = match.route.path
        if (!path) continue

        pattern = path.startsWith('/')
            ? path
            : `${pattern.replace(/\/$/, '')}/${path}`
    }

    return pattern === '' ? '/' : pattern.replace(/(.)\/$/, '$1')
}

/** What axios would send for us: the cookie Laravel sets, as the header it checks. */
const xsrfToken = (): string => {
    const cookie = document.cookie
        .split('; ')
        .find(entry => entry.startsWith('XSRF-TOKEN='))

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : ''
}

/**
 * Raw `fetch` rather than axios, for `keepalive`: the last batch goes out as
 * the tab is hidden or closed, and only a keepalive request outlives the page.
 * axios can't send one, and `sendBeacon` can't carry the CSRF header.
 */
const sendPageViews = (views: PageViews): Promise<Response> =>
    fetch('/api/client/telemetry/page-views', {
        method: 'POST',
        keepalive: true,
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(views),
    })

/**
 * Counts page views for the telemetry report (docs/telemetry.md).
 *
 * The panel is a client-side app, so the server never sees a page change.
 * This counts each finished navigation by its route pattern and sends the
 * counts every five minutes and when the tab is hidden. A change to the query
 * string alone, like paging a table, is not a view, and neither are the
 * sign-in screens.
 *
 * Does nothing at all when telemetry is off.
 */
const trackPageViews = (
    router: Router,
    enabled: boolean,
    signedIn: () => boolean
) => {
    if (!enabled) return

    let pending: PageViews = {}
    let lastPathname: string | undefined

    const count = (state: RouterState) => {
        if (!state.initialized || state.navigation.state !== 'idle') return
        if (state.location.pathname === lastPathname) return

        lastPathname = state.location.pathname

        // A signed-out visitor is on their way to the sign-in screen, whatever the address.
        if (!signedIn()) return

        const pattern = routePattern(state)

        // Not usage: the sign-in screen, and the "page not found" catch-all.
        if (pattern.startsWith('/auth') || pattern.includes('*')) return

        pending[pattern] = (pending[pattern] ?? 0) + 1
    }

    const flush = () => {
        if (Object.keys(pending).length === 0) return

        const views = pending
        pending = {}

        // A batch that fails is dropped, not retried: lost counts are an
        // undercount, not an error worth showing anyone.
        sendPageViews(views).catch(() => {})
    }

    // The page the tab opened on: subscribers only hear about later changes.
    count(router.state)
    router.subscribe(count)

    window.setInterval(flush, FLUSH_INTERVAL_MS)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') flush()
    })
}

export default trackPageViews
