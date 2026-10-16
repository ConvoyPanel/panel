import { BASE, launch, newContext, login, collectErrors } from '/opt/sbx-e2e/browser.mjs'
const OUT = '/Users/eric/Documents/GitHub/panel/storage/uiverify/out'
const browser = await launch()
const ctx = await newContext(browser, { viewport: { width: 1600, height: 1000 }, deviceScaleFactor: 1 })
const page = await login(ctx, { email: 'ochristiansen@example.net', password: 'Zzz!98765' })
const errors = collectErrors(page)
await page.goto(BASE + '/', { waitUntil: 'networkidle' })
await page.waitForTimeout(800)
const href = await page.evaluate(() => document.querySelector('a[href*="/servers/"]')?.getAttribute('href'))
console.log('server:', href)
await page.goto(BASE + href + '/sharing', { waitUntil: 'networkidle' })
await page.waitForTimeout(1800)
console.log('h1:', await page.evaluate(() => document.querySelector('h1')?.textContent?.trim()))
console.log('text:', JSON.stringify((await page.evaluate(() => document.querySelector('main').innerText)).slice(0, 400)))
console.log('tabs:', JSON.stringify(await page.evaluate(() => [...document.querySelectorAll('nav a, [role=tab]')].map(e => e.textContent.trim()).slice(0,14))))
await page.screenshot({ path: OUT + '/shot-sharing.png' })
const btn = page.getByRole('button', { name: /share|invite|add/i }).first()
if (await btn.isVisible().catch(()=>false)) {
  await btn.click(); await page.waitForTimeout(1500)
  console.log('dialog:', JSON.stringify(await page.evaluate(() => ({
    n: document.querySelectorAll('[role=dialog]').length,
    titles: [...document.querySelectorAll('[role=dialog] h2')].map(e=>e.textContent.trim()),
    perms: [...document.querySelectorAll('[role=dialog] [role=checkbox], [role=dialog] input[type=checkbox]')].length,
  }))))
  await page.screenshot({ path: OUT + '/shot-sharing-dialog.png' })
} else console.log('no share trigger')
console.log('errors:', JSON.stringify(errors.filter(e=>!/401|403/.test(e))))
await browser.close()
