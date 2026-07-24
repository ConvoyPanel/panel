/*
 * Playwright helpers for driving the sandbox's own ddev app.
 *
 * The dev kit copies this to /opt/sbx-e2e/browser.mjs on every start, next to a
 * pinned `playwright` install. Import it by absolute path from a throwaway
 * script anywhere (e.g. your scratchpad) — resolving `playwright` relative to
 * /opt/sbx-e2e means the repo never needs a devDependency for a local probe:
 *
 *     import { BASE, launch, newContext, login, capture } from '/opt/sbx-e2e/browser.mjs'
 *
 *     const browser = await launch()
 *     const ctx = await newContext(browser)
 *     const page = await login(ctx, { email: '…', password: '…' })
 *     await capture(ctx, { url: '/admin/nodes', width: 768, path: '/tmp/nodes.png' })
 *     await browser.close()
 */
import { chromium } from 'playwright'
import { execFileSync } from 'node:child_process'

export const BASE = process.env.SBX_APP_URL ?? 'https://convoy.ddev.site'

const PROXY = process.env.HTTPS_PROXY ?? 'http://gateway.docker.internal:3128'

/*
 * Chromium hands hostnames to the proxy rather than resolving them, and the
 * sandbox proxy resolves them on the HOST — so an unbypassed request to
 * convoy.ddev.site drives your real host app (leaked sessions, mutated data).
 * Bypassing the proxy for the app keeps it on this sandbox's loopback; the
 * preflight below is the backstop that refuses to run if it ever slips.
 */
const BYPASS = '.ddev.site,localhost,127.0.0.1'

export function assertSandboxApp(base = BASE) {
    const ip = execFileSync('curl', ['-sk', `${base}/up`, '-o', '/dev/null', '-w', '%{remote_ip}'])
        .toString()
        .trim()

    if (ip !== '127.0.0.1') {
        throw new Error(
            `refusing to drive ${base}: it answered from ${ip || '(unreachable)'}, not this ` +
                `sandbox's ddev (127.0.0.1). Check 'ddev describe', that .ddev.site is in ` +
                `NO_PROXY, and that /etc/hosts is the writable overmount the dev kit sets up.`
        )
    }
}

export async function launch(options = {}) {
    assertSandboxApp()

    return chromium.launch({ proxy: { server: PROXY, bypass: BYPASS }, ...options })
}

// The ddev cert is mkcert-signed and that CA isn't in the sandbox trust store.
export async function newContext(browser, options = {}) {
    return browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1440, height: 1000 },
        deviceScaleFactor: 2,
        ...options,
    })
}

export async function login(context, { email, password } = {}) {
    const page = await context.newPage()

    await page.goto(`${BASE}/auth/login`, { waitUntil: 'domcontentloaded' })
    await page.getByLabel(/email/i).fill(email ?? process.env.SBX_APP_EMAIL)
    await page.getByLabel(/password/i).fill(password ?? process.env.SBX_APP_PASSWORD)
    await page.getByRole('button', { name: /sign in|log in|login/i }).click()
    await page.waitForURL(u => !u.pathname.includes('/auth/login'), { timeout: 30_000 })

    return page
}

/*
 * Screenshot one route at one viewport. Returns the horizontal overflow in px,
 * which is the layout failure mode worth failing a visual check on.
 */
export async function capture(context, { url, path, width = 1440, height = 1000, settle = 1000 }) {
    const page = await context.newPage()

    try {
        await page.setViewportSize({ width, height })
        await page.goto(BASE + url, { waitUntil: 'networkidle' })
        await page.waitForTimeout(settle)
        await page.screenshot({ path, fullPage: true })

        return page.evaluate(
            () => document.documentElement.scrollWidth - document.documentElement.clientWidth
        )
    } finally {
        await page.close()
    }
}

// Attach before navigating; the returned array fills as the page misbehaves.
export function collectErrors(page) {
    const errors = []

    page.on('pageerror', e => errors.push(`pageerror: ${e.message}`))
    page.on('console', m => m.type() === 'error' && errors.push(`console: ${m.text()}`))

    return errors
}
