/*
 * Client-area sweep: the server detail pages.
 *
 * These are the densest screens in the app and the ones the seeded data reaches
 * least well, so a failure here is ambiguous by default. The sweep therefore
 * separates two outcomes that look identical in a screenshot: a page that fails
 * to render, and a page that renders an honest error because the node behind it
 * is unreachable. Only the first is a UI defect.
 */
import { BASE, launch, newContext, login, collectErrors } from '/opt/sbx-e2e/browser.mjs'
import { writeFile } from 'node:fs/promises'

const OUT = '/Users/eric/Documents/GitHub/panel/storage/uiverify/out'

// The real client routes, read off resources/scripts/routes/_app/servers.$serverUuid/.
// There is no client `/settings`; guessing one only proves the 404 page works.
const TABS = [
    '',
    '/graphs',
    '/networking',
    '/storage',
    '/backups',
    '/security',
    '/firewall',
    '/activity',
    '/iso-library',
    '/rebuild',
]

const findings = []

const flag = (where, what) => {
    findings.push({ where, what })
    console.log(`  ! ${where}: ${what}`)
}

const browser = await launch()
const ctx = await newContext(browser)

const page = await login(ctx, {
    email: process.env.SWEEP_EMAIL ?? 'admin@example.com',
    password: process.env.SWEEP_PASSWORD ?? 'Zzz!98765',
})

const errors = collectErrors(page)

await page.goto(BASE + '/', { waitUntil: 'networkidle' })
await page.waitForTimeout(900)

const first = await page.evaluate(() => {
    const a = [...document.querySelectorAll('a[href*="/servers/"]')][0]

    return a ? a.getAttribute('href') : null
})

console.log('first server href:', first)

if (!first) {
    console.log('no server visible to this account; client sweep cannot run')
    await browser.close()
    process.exit(0)
}

for (const tab of TABS) {
    const url = first + tab
    const before = errors.length

    await page.setViewportSize({ width: 1440, height: 900 })
    await page.goto(BASE + url, { waitUntil: 'domcontentloaded' })
    await page.waitForTimeout(2500)

    const state = await page.evaluate(() => {
        const text = document.body.innerText
        const d = document.documentElement

        return {
            h1: document.querySelector('h1')?.textContent?.trim() ?? null,
            chars: text.length,
            looksUnreachable: /unreachable|could not connect|failed to connect|failed to reach|offline/i.test(text),
            looksCrashed: /something went wrong|unexpected error|application error|chunkloaderror/i.test(text),
            over: d.scrollWidth - d.clientWidth,
        }
    })

    await page.setViewportSize({ width: 390, height: 900 })
    await page.waitForTimeout(500)

    const narrow = await page.evaluate(
        () => document.documentElement.scrollWidth - document.documentElement.clientWidth
    )

    if (state.looksCrashed) flag(url, 'page shows a crash or error boundary')
    if (!state.h1 && !state.looksUnreachable) flag(url, 'no h1 and no unreachable notice')
    if (state.over > 0) flag(url, 'horizontal overflow ' + state.over + 'px at 1440')
    if (narrow > 0) flag(url, 'horizontal overflow ' + narrow + 'px at 390')

    const newErrors = errors.slice(before).filter(e => !/401|403|Failed to load resource/.test(e))

    if (newErrors.length) flag(url, 'console: ' + newErrors.join(' | '))

    await page.setViewportSize({ width: 1440, height: 900 })
    await page.waitForTimeout(300)

    const name = tab === '' ? '_overview' : tab.split('/').join('_')

    await page.screenshot({ path: OUT + '/client' + name + '.png' })

    console.log(
        '  ' +
            (tab === '' ? '/overview' : tab).padEnd(13) +
            ' h1=' +
            JSON.stringify(state.h1) +
            ' chars=' +
            state.chars +
            ' unreachable=' +
            state.looksUnreachable +
            ' 1440=' +
            state.over +
            ' 390=' +
            narrow
    )
}

await browser.close()
await writeFile(OUT + '/sweep-client.json', JSON.stringify({ findings, errors }, null, 2))

console.log('\n==== ' + findings.length + ' finding(s) ====')
findings.forEach(f => console.log('- ' + f.where + ': ' + f.what))
