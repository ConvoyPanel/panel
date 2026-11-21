import PageViewController from '@/wayfinder/actions/App/Http/Controllers/Client/PageViewController'

/** Route id => times it was opened. Nothing else goes in the body. */
export type PageViews = Record<string, number>

/** What axios would send for us: the cookie Laravel sets, as the header it checks. */
const xsrfToken = (): string => {
    const cookie = document.cookie
        .split('; ')
        .find(entry => entry.startsWith('XSRF-TOKEN='))

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : ''
}

/**
 * Raw `fetch` rather than `apiFetch`, for `keepalive`: the last batch goes out
 * as the tab is hidden or closed, and only a keepalive request outlives the
 * page. axios can't send one. `sendBeacon` can't either, since it can't carry
 * the CSRF header.
 */
export const sendPageViews = (views: PageViews): Promise<Response> =>
    fetch(PageViewController.url(), {
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
