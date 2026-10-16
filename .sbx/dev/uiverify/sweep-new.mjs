/*
 * Sweep for the screens added by the image, permission and migration work.
 *
 * Same contract as sweep.mjs: render, overflow at both widths, console, dark.
 * Plus the interactions these features are actually made of, recorded, because
 * a catalogue sheet and a permission picker are where a dialog regression would
 * show first.
 */
import { BASE, launch, newContext, login, collectErrors } from '/opt/sbx-e2e/browser.mjs'
import { record, contactSheet, dumpFrames } from './filmstrip.mjs'
import { mkdir, writeFile } from 'node:fs/promises'

const OUT = '/Users/eric/Documents/GitHub/panel/storage/uiverify/out'
const CREDS = { email: 'admin@example.com', password: 'Zzz!98765' }

const ROUTES = [
    '/admin/images',
    '/admin/roles',
    '/admin/adoptable-guests',
    '/admin/settings/permissions',
    '/admin/users',
]

const findings = []

const flag = (where, what) => {
    findings.push({ where, what })
    console.log('  ! ' + where + ': ' + what)
}

await mkdir(OUT, { recursive: true })

const browser = await launch()
const ctx = await newContext(browser)
const page = await login(ctx, CREDS)
const errors = collectErrors(page)
const scratch = await ctx.newPage()

const overflowAt = async width => {
    await page.setViewportSize({ width, height: 900 })
    await page.waitForTimeout(450)

    return page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)
}

console.log('== static ==')

for (const route of ROUTES) {
    const before = errors.length

    await page.setViewportSize({ width: 1440, height: 900 })
    await page.goto(BASE + route, { waitUntil: 'networkidle' })
    await page.waitForTimeout(900)

    const info = await page.evaluate(() => ({
        h1: document.querySelector('h1')?.textContent?.trim() ?? null,
        text: document.body.innerText.slice(0, 160),
        crashed: /something went wrong|unexpected error|application error/i.test(document.body.innerText),
    }))

    const wide = await overflowAt(1440)
    const narrow = await overflowAt(390)

    if (info.crashed) flag(route, 'crash or error boundary')
    if (!info.h1) flag(route, 'no h1')
    if (wide > 0) flag(route, 'overflow ' + wide + 'px at 1440')
    if (narrow > 0) flag(route, 'overflow ' + narrow + 'px at 390')

    const fresh = errors.slice(before).filter(e => !/401|403|Failed to load resource/.test(e))

    if (fresh.length) flag(route, 'console: ' + fresh.join(' | '))

    await page.setViewportSize({ width: 1440, height: 900 })
    await page.waitForTimeout(300)
    await page.screenshot({ path: OUT + '/new' + route.split('/').join('_') + '.png' })

    console.log('  ' + route.padEnd(30) + ' h1=' + JSON.stringify(info.h1) + ' 1440=' + wide + ' 390=' + narrow)
}

console.log('\n== interactions ==')

const interaction = async (name, route, fn) => {
    if (route) {
        await page.goto(BASE + route, { waitUntil: 'networkidle' })
        await page.waitForTimeout(800)
    }

    const before = errors.length
    let frames

    try {
        frames = await record(page, fn)
    } catch (e) {
        flag(name, 'threw: ' + String(e).split('\n')[0])

        return null
    }

    const fresh = errors.slice(before).filter(e => !/401|403|Failed to load resource/.test(e))

    if (fresh.length) flag(name, 'console: ' + fresh.join(' | '))

    let blanks = 0

    for (const f of frames.slice(1, -1)) {
        const spread = await scratch.evaluate(async d => {
            const img = await new Promise(res => {
                const i = new Image()
                i.onload = () => res(i)
                i.src = 'data:image/jpeg;base64,' + d
            })
            const w = 120
            const h = Math.max(1, Math.round((img.height / img.width) * w))
            const c = new OffscreenCanvas(w, h)
            const g = c.getContext('2d')
            g.drawImage(img, 0, 0, w, h)
            const px = g.getImageData(0, 0, w, h).data
            let min = 255
            let max = 0
            for (let i = 0; i < px.length; i += 4) {
                const v = (px[i] + px[i + 1] + px[i + 2]) / 3
                if (v < min) min = v
                if (v > max) max = v
            }

            return max - min
        }, f.data)

        if (spread < 15) blanks++
    }

    if (blanks) flag(name, blanks + ' near-blank frame(s) mid-interaction')

    console.log('  ' + name.padEnd(32) + ' ' + frames.length + ' frames / ' + frames.at(-1).ms + 'ms')

    await contactSheet(ctx, frames, { outPath: OUT + '/' + name + '.png', title: name, columns: 6, cellWidth: 260 })

    if (blanks || fresh.length) await dumpFrames(frames, OUT + '/frames/' + name)

    return frames
}

const dialogs = () =>
    page.evaluate(() => ({
        dialogs: document.querySelectorAll('[role="dialog"],[role="alertdialog"]').length,
        backdrops: document.querySelectorAll('[data-slot="dialog-backdrop"],[class*="backdrop"]').length,
        titles: [...document.querySelectorAll('[role="dialog"] h2')].map(e => e.textContent.trim()),
    }))

// The image catalogue: the whole point of Source A.
await page.goto(BASE + '/admin/images', { waitUntil: 'networkidle' })
await page.waitForTimeout(900)

const catalogueTrigger = page.getByRole('button', { name: /catalog|catalogue|browse|import/i }).first()

if (await catalogueTrigger.isVisible().catch(() => false)) {
    await interaction('new-image-catalogue-open', null, async () => {
        await catalogueTrigger.click()
        await page.waitForTimeout(2500)
    })

    const st = await dialogs()
    console.log('     ' + JSON.stringify(st))

    if (st.backdrops > 1) flag('new-image-catalogue-open', st.backdrops + ' backdrops for one sheet')

    const rows = await page.evaluate(() => document.body.innerText.match(/AlmaLinux|Debian|Ubuntu|Rocky|Fedora/g)?.length ?? 0)

    console.log('     catalogue entries visible: ' + rows)

    if (!rows) flag('new-image-catalogue-open', 'sheet opened but listed no catalogue entries')

    await page.keyboard.press('Escape')
    await page.waitForTimeout(600)
} else {
    flag('/admin/images', 'no catalogue trigger found in the toolbar')
}

// Roles: the replacement for blanket root admin.
await interaction('new-roles-page-scroll', '/admin/roles', async () => {
    await page.mouse.move(700, 500)
    await page.mouse.wheel(0, 600)
    await page.waitForTimeout(500)
    await page.mouse.wheel(0, -600)
    await page.waitForTimeout(500)
})

const roleRow = page.getByRole('button', { name: /duplicate|edit/i }).first()

if (await roleRow.isVisible().catch(() => false)) {
    await interaction('new-role-dialog-open', null, async () => {
        await roleRow.click()
        await page.waitForTimeout(1400)
    })

    const st = await dialogs()
    console.log('     ' + JSON.stringify(st))

    if (st.dialogs === 0) flag('new-role-dialog-open', 'no dialog opened')
    if (st.backdrops > 1) flag('new-role-dialog-open', st.backdrops + ' backdrops stacked')

    await page.keyboard.press('Escape')
    await page.waitForTimeout(600)

    const after = await dialogs()

    if (after.dialogs > 0) flag('new-role-dialog-open', 'Escape did not close it')
}

// The guest kill switch.
await interaction('new-permission-settings', '/admin/settings/permissions', async () => {
    await page.mouse.move(700, 500)
    await page.mouse.wheel(0, 400)
    await page.waitForTimeout(600)
})

await browser.close()
await writeFile(OUT + '/sweep-new.json', JSON.stringify({ findings, errors }, null, 2))

console.log('\n==== ' + findings.length + ' finding(s) ====')
findings.forEach(f => console.log('- ' + f.where + ': ' + f.what))
