import { BASE, launch, newContext, login } from '/opt/sbx-e2e/browser.mjs'

const browser = await launch()
const ctx = await newContext(browser, { viewport: { width: 1600, height: 1000 }, deviceScaleFactor: 1 })
const page = await login(ctx, { email: 'admin@example.com', password: 'Zzz!98765' })

await page.goto(BASE + '/admin/roles', { waitUntil: 'networkidle' })
await page.waitForTimeout(1200)

console.log(
    JSON.stringify(
        await page.evaluate(() => {
            const table = document.querySelector('table')
            if (!table) return { error: 'no table' }

            const headers = [...table.querySelectorAll('thead th')].map(th => ({
                label: th.innerText.trim(),
                width: Math.round(th.getBoundingClientRect().width),
            }))

            const rows = [...table.querySelectorAll('tbody tr')].map(tr => ({
                height: Math.round(tr.getBoundingClientRect().height),
                cells: [...tr.querySelectorAll('td')].map(td => ({
                    w: Math.round(td.getBoundingClientRect().width),
                    lines: Math.round(td.getBoundingClientRect().height / 20),
                    text: td.innerText.trim().slice(0, 40).replace(/\n/g, ' / '),
                })),
            }))

            return { tableWidth: Math.round(table.getBoundingClientRect().width), headers, rows }
        }),
        null,
        1
    )
)

await browser.close()
