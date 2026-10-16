import { BASE, launch, newContext, login, collectErrors } from '/opt/sbx-e2e/browser.mjs'
const OUT = '/Users/eric/Documents/GitHub/panel/storage/uiverify/out'
const browser = await launch()
const ctx = await newContext(browser, { viewport: { width: 1600, height: 1000 }, deviceScaleFactor: 1 })
const page = await login(ctx, { email: 'admin@example.com', password: 'Zzz!98765' })
const errors = collectErrors(page)

await page.goto(BASE + '/admin/images', { waitUntil: 'networkidle' })
await page.waitForTimeout(900)
await page.getByRole('button', { name: /catalog|catalogue|browse/i }).first().click()
await page.waitForTimeout(3000)
const rows = await page.evaluate(() => [...document.querySelectorAll('[role="dialog"] [class*="ItemDescription"], [role="dialog"] p')].map(e => e.textContent.trim()).filter(t => /transfer/.test(t)).slice(0, 4))
console.log('catalogue rows:'); rows.forEach(r => console.log('  ', r))
await page.screenshot({ path: OUT + '/builds-catalogue.png' })
await page.keyboard.press('Escape')
await page.waitForTimeout(800)
console.log('errors:', JSON.stringify(errors.filter(e => !/401|403/.test(e))))
await browser.close()
