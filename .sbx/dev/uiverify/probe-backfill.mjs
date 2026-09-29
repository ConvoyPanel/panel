import { BASE, launch, newContext, login } from '/opt/sbx-e2e/browser.mjs'
const browser = await launch()
const ctx = await newContext(browser)
const page = await login(ctx, { email: 'admin@example.com', password: 'Zzz!98765' })
await page.goto(BASE + '/admin/images', { waitUntil: 'networkidle' })
await page.waitForTimeout(800)
const res = await page.evaluate(async () => {
  const xsrf = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) ?? [])[1] ?? '')
  const r = await fetch('/api/admin/images/registry/imports', {
    method: 'POST',
    headers: { 'X-XSRF-TOKEN': xsrf, 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ templates: ['debian-12-amd64'] }),
  })
  return { status: r.status, body: (await r.text()).slice(0, 300) }
})
console.log(JSON.stringify(res, null, 1))
await browser.close()
