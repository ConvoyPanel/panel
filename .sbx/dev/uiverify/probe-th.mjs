import { BASE, launch, newContext, login } from '/opt/sbx-e2e/browser.mjs'
const browser = await launch()
const ctx = await newContext(browser, { viewport: { width: 1600, height: 1000 }, deviceScaleFactor: 1 })
const page = await login(ctx, { email: 'admin@example.com', password: 'Zzz!98765' })
await page.goto(BASE + '/admin/roles', { waitUntil: 'networkidle' })
await page.waitForTimeout(1200)
console.log(JSON.stringify(await page.evaluate(() => {
    const table = document.querySelector('table')
    return {
        tableLayout: getComputedStyle(table).tableLayout,
        ths: [...table.querySelectorAll('thead th')].map(th => ({
            text: th.innerText.trim(), cls: th.className,
            declared: getComputedStyle(th).width,
            actual: Math.round(th.getBoundingClientRect().width),
        })),
    }
}), null, 1))
await browser.close()
