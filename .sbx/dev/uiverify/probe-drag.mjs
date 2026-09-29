import { BASE, launch, newContext, login, collectErrors } from '/opt/sbx-e2e/browser.mjs'
import { record, contactSheet } from './filmstrip.mjs'
import { dragSmooth } from './drag.mjs'
import { execFileSync } from 'node:child_process'
import { mkdir } from 'node:fs/promises'

const OUT = '/Users/eric/Documents/GitHub/panel/storage/uiverify/out'
const IMG = '/tmp/avatar-test.png'

await mkdir(OUT, { recursive: true })

// A busy, asymmetric source image: a flat colour would make it impossible to
// see whether the crop actually moved.
execFileSync('ffmpeg', [
    '-y',
    '-f', 'lavfi',
    '-i', 'testsrc=size=600x600:rate=1',
    '-frames:v', '1',
    IMG,
], { stdio: 'ignore' })

const browser = await launch()
const ctx = await newContext(browser)
const page = await login(ctx, { email: 'admin@example.com', password: 'Zzz!98765' })
const errors = collectErrors(page)

await page.goto(`${BASE}/account`, { waitUntil: 'networkidle' })
await page.waitForTimeout(900)

console.log('h1:', await page.evaluate(() => document.querySelector('h1')?.textContent?.trim()))

const inputs = await page.evaluate(() =>
    [...document.querySelectorAll('input[type=file]')].map(i => ({ accept: i.accept, hidden: i.offsetParent === null }))
)
console.log('file inputs:', JSON.stringify(inputs))

if (!inputs.length) {
    console.log('NO file input on /account; cannot reach the cropper')
    await browser.close()
    process.exit(0)
}

await page.setInputFiles('input[type=file]', IMG)
await page.waitForTimeout(1200)

const dlg = await page.evaluate(() => ({
    dialogs: document.querySelectorAll('[role="dialog"]').length,
    titles: [...document.querySelectorAll('[role="dialog"] h2')].map(e => e.textContent.trim()),
}))
console.log('after upload:', JSON.stringify(dlg))

if (!dlg.dialogs) {
    console.log('cropper did not open')
    await browser.close()
    process.exit(0)
}

// The draggable surface is the image stage inside the dialog.
const stage = await page.evaluate(() => {
    const d = document.querySelector('[role="dialog"]')
    const img = d.querySelector('img')
    const el = img?.closest('div')
    if (!el) return null
    const r = el.getBoundingClientRect()

    return { x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.width), h: Math.round(r.height) }
})
console.log('stage:', JSON.stringify(stage))

const cx = stage.x + stage.w / 2
const cy = stage.y + stage.h / 2

const frames = await record(
    page,
    () => dragSmooth(page, { x: cx, y: cy }, { x: cx - 90, y: cy - 60 }),
    { settleAfter: 500 }
)

console.log('drag frames:', frames.length, 'over', frames.at(-1).ms, 'ms')

await contactSheet(ctx, frames, {
    outPath: `${OUT}/drag-avatar-crop.png`,
    title: 'avatar cropper: drag the image',
    columns: 6,
    cellWidth: 260,
})

console.log('errors:', JSON.stringify(errors))
await browser.close()
