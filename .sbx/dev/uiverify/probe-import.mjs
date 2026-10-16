/*
 * Import a real cofoundry image through the real UI, and record it frame by
 * frame. This is the end-to-end question the whole images workstream exists to
 * answer: does an entry in the published registry become an image Convoy can
 * build from.
 */
import { BASE, launch, newContext, login, collectErrors } from '/opt/sbx-e2e/browser.mjs'
import { record, contactSheet } from './filmstrip.mjs'

const OUT = '/Users/eric/Documents/GitHub/panel/storage/uiverify/out'

const browser = await launch()
const ctx = await newContext(browser, { viewport: { width: 1600, height: 1000 }, deviceScaleFactor: 1 })
const page = await login(ctx, { email: 'admin@example.com', password: 'Zzz!98765' })
const errors = collectErrors(page)

const api = []
page.on('response', r => {
    const u = r.url().replace(BASE, '')
    if (u.includes('/api/')) api.push(r.status() + ' ' + r.request().method() + ' ' + u)
})

await page.goto(BASE + '/admin/images', { waitUntil: 'networkidle' })
await page.waitForTimeout(900)
await page.getByRole('button', { name: /catalog|catalogue|browse/i }).first().click()
await page.waitForTimeout(3000)

// Debian 12 is small enough to be a sane first import and is a real entry.
const row = page.getByRole('button', { name: /^import$/i }).nth(4)

console.log('import buttons:', await page.getByRole('button', { name: /^import$/i }).count())

const frames = await record(
    page,
    async () => {
        await row.click()
        await page.waitForTimeout(6000)
    },
    { settleAfter: 1500 }
)

console.log('frames:', frames.length, 'over', frames.at(-1).ms, 'ms')

await contactSheet(ctx, frames, {
    outPath: OUT + '/import-from-catalogue.png',
    title: 'import a cofoundry image from the catalogue',
    columns: 6,
    cellWidth: 260,
})

await page.keyboard.press('Escape')
await page.waitForTimeout(1200)
await page.reload({ waitUntil: 'networkidle' })
await page.waitForTimeout(1500)
await page.screenshot({ path: OUT + '/images-after-import.png' })

console.log('page text after import:', JSON.stringify((await page.evaluate(() => document.querySelector('main').innerText)).slice(0, 400)))
console.log('api:', JSON.stringify(api.filter(a => !a.startsWith('200 GET /api/client')), null, 1))
console.log('errors:', JSON.stringify(errors))

await browser.close()
