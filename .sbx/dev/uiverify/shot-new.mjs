import { BASE, launch, newContext, login } from '/opt/sbx-e2e/browser.mjs'

const OUT = '/Users/eric/Documents/GitHub/panel/storage/uiverify/out'

const browser = await launch()
const ctx = await newContext(browser, { viewport: { width: 1600, height: 1000 }, deviceScaleFactor: 1 })
const page = await login(ctx, { email: 'admin@example.com', password: 'Zzz!98765' })

// Catalogue sheet, open, with real CDN content.
await page.goto(BASE + '/admin/images', { waitUntil: 'networkidle' })
await page.waitForTimeout(900)
await page.getByRole('button', { name: /catalog|catalogue|browse|import/i }).first().click()
await page.waitForTimeout(3000)
await page.screenshot({ path: OUT + '/shot-catalogue.png' })
console.log('catalogue titles:', JSON.stringify(await page.evaluate(() => [...document.querySelectorAll('[role="dialog"] h2, [role="dialog"] h3')].map(e => e.textContent.trim()).slice(0, 6))))
await page.keyboard.press('Escape')
await page.waitForTimeout(600)

// Roles.
await page.goto(BASE + '/admin/roles', { waitUntil: 'networkidle' })
await page.waitForTimeout(1200)
await page.screenshot({ path: OUT + '/shot-roles.png' })
console.log('roles controls:', JSON.stringify(await page.evaluate(() =>
    [...document.querySelectorAll('main button, main a[href]')].filter(e => e.offsetParent).map(e => e.textContent.trim().slice(0, 28)).slice(0, 20)
)))

// Guest switch.
await page.goto(BASE + '/admin/settings/permissions', { waitUntil: 'networkidle' })
await page.waitForTimeout(1200)
await page.screenshot({ path: OUT + '/shot-permission-settings.png' })
console.log('settings text:', JSON.stringify((await page.evaluate(() => document.querySelector('main').innerText)).slice(0, 500)))

// Unmanaged guests.
await page.goto(BASE + '/admin/adoptable-guests', { waitUntil: 'networkidle' })
await page.waitForTimeout(2000)
await page.screenshot({ path: OUT + '/shot-adoptable.png' })
console.log('adoptable text:', JSON.stringify((await page.evaluate(() => document.querySelector('main').innerText)).slice(0, 320)))

await browser.close()
