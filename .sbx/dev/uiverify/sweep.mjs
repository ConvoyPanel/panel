/*
 * Broad interaction sweep across the admin area.
 *
 * Two kinds of check, because they fail differently:
 *  - static: does the screen render, at desktop and at phone width, without
 *    horizontal overflow or console errors.
 *  - dynamic: does an interaction produce a clean sequence of frames. Dialogs
 *    get a backdrop count, because a backdrop that goes 1 -> 0 -> 1 is the
 *    modal flash the v5 nested-dialog work exists to prevent, and a single
 *    still cannot show it.
 *
 * Only flagged interactions get a contact sheet, so a clean run stays cheap.
 */
import { BASE, launch, newContext, login, collectErrors } from '/opt/sbx-e2e/browser.mjs'
import { record, contactSheet, dumpFrames } from './filmstrip.mjs'
import { mkdir, writeFile } from 'node:fs/promises'

const OUT = '/Users/eric/Documents/GitHub/panel/storage/uiverify/out'
const CREDS = { email: 'admin@example.com', password: 'Zzz!98765' }

const ROUTES = [
    '/',
    '/admin/locations',
    '/admin/nodes',
    '/admin/anchors',
    '/admin/servers',
    '/admin/ipam',
    '/admin/storage',
    '/admin/images',
    '/admin/isos',
    '/admin/server-presets',
    '/admin/users',
    '/admin/tokens',
    '/admin/audit-log',
    '/admin/settings',
]

const findings = []
const flag = (where, what) => {
    findings.push({ where, what })
    console.log(`  ! ${where}: ${what}`)
}

const overflowAt = async (page, width) => {
    await page.setViewportSize({ width, height: 900 })
    await page.waitForTimeout(450)

    return page.evaluate(() => {
        const d = document.documentElement
        const over = d.scrollWidth - d.clientWidth
        let culprits = []
        if (over > 0) {
            culprits = [...document.querySelectorAll('body *')]
                .filter(el => el.getBoundingClientRect().right > d.clientWidth + 1)
                .slice(0, 4)
                .map(el => `${el.tagName.toLowerCase()}.${String(el.className).split(' ').slice(0, 3).join('.')}`)
        }

        return { over, culprits }
    })
}

const dialogState = page =>
    page.evaluate(() => ({
        dialogs: document.querySelectorAll('[role="dialog"], [role="alertdialog"]').length,
        backdrops: document.querySelectorAll('[data-slot="dialog-backdrop"], [class*="backdrop"]').length,
        titles: [...document.querySelectorAll('[role="dialog"] h2, [role="dialog"] [data-slot="dialog-title"]')]
            .map(e => e.textContent.trim())
            .slice(0, 4),
    }))

const run = async () => {
    await mkdir(OUT, { recursive: true })

    const browser = await launch()
    const ctx = await newContext(browser)
    const page = await login(ctx, CREDS)
    const errors = collectErrors(page)
    const scratch = await ctx.newPage()

    // ---------- static pass ----------
    console.log('== static pass ==')
    for (const route of ROUTES) {
        const before = errors.length
        await page.setViewportSize({ width: 1440, height: 900 })

        const resp = await page.goto(BASE + route, { waitUntil: 'networkidle' }).catch(e => ({ err: e }))
        if (resp?.err) {
            flag(route, `navigation failed: ${String(resp.err).split('\n')[0]}`)
            continue
        }
        await page.waitForTimeout(700)

        const h1 = await page.evaluate(() => document.querySelector('h1')?.textContent?.trim() ?? null)
        const wide = await overflowAt(page, 1440)
        const narrow = await overflowAt(page, 390)

        if (wide.over > 0) flag(route, `horizontal overflow ${wide.over}px at 1440 (${wide.culprits.join(', ')})`)
        if (narrow.over > 0) flag(route, `horizontal overflow ${narrow.over}px at 390 (${narrow.culprits.join(', ')})`)
        if (!h1) flag(route, 'no <h1> on the page')

        const newErrors = errors.slice(before)
        if (newErrors.length) flag(route, `console: ${newErrors.join(' | ')}`)

        console.log(`  ${route.padEnd(24)} h1=${JSON.stringify(h1)} 1440=${wide.over}px 390=${narrow.over}px`)
    }

    // ---------- dynamic pass ----------
    console.log('\n== dynamic pass ==')
    await page.setViewportSize({ width: 1440, height: 900 })

    const interaction = async (name, route, fn) => {
        if (route) {
            await page.goto(BASE + route, { waitUntil: 'networkidle' })
            await page.waitForTimeout(600)
        }

        const before = errors.length
        let frames

        try {
            frames = await record(page, fn)
        } catch (e) {
            flag(name, `interaction threw: ${String(e).split('\n')[0]}`)

            return
        }

        const after = errors.slice(before)
        if (after.length) flag(name, `console during interaction: ${after.join(' | ')}`)

        // A frame that is essentially one flat colour mid-interaction is a blank paint.
        let blanks = 0
        for (const f of frames.slice(1, -1)) {
            const spread = await scratch.evaluate(async d => {
                const img = await new Promise(res => {
                    const i = new Image()
                    i.onload = () => res(i)
                    i.src = `data:image/jpeg;base64,${d}`
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

        if (blanks) flag(name, `${blanks} near-blank frame(s) mid-interaction`)

        console.log(`  ${name.padEnd(34)} ${frames.length} frames / ${frames.at(-1).ms}ms${blanks ? ` BLANKS=${blanks}` : ''}`)

        if (blanks || after.length) {
            await contactSheet(ctx, frames, { outPath: `${OUT}/${name}.png`, title: name })
            await dumpFrames(frames, `${OUT}/frames/${name}`)
        }

        return frames
    }

    // Client-side route transitions: the shell must survive.
    await page.goto(`${BASE}/admin/servers`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(700)

    for (const label of ['Nodes', 'IPAM', 'Storage', 'Users', 'Audit Log']) {
        await interaction(`route-click-${label.replace(/\s+/g, '-').toLowerCase()}`, null, async () => {
            await page.getByRole('link', { name: label, exact: true }).click()
            await page.waitForLoadState('networkidle')
        })
    }

    // Dialogs: watch the backdrop count across open and close.
    // Only affordances that really open a dialog belong here. "Add node" is a
    // link to /admin/nodes/create ("Enroll a node"), a full page rather than a
    // dialog, so asserting a dialog on it reports a bug that does not exist.
    const dialogProbes = [
        ['dialog-add-location', '/admin/locations', /add location/i, 'button'],
        ['dialog-add-image-group', '/admin/images', /add image group/i, 'button'],
        ['dialog-add-user', '/admin/users', /add user|create user/i, 'button'],
    ]

    for (const [name, route, rx, role] of dialogProbes) {
        await page.goto(BASE + route, { waitUntil: 'networkidle' })
        await page.waitForTimeout(700)

        const trigger = page.getByRole(role, { name: rx }).first()
        if (!(await trigger.isVisible().catch(() => false))) {
            flag(name, `trigger ${rx} not visible on ${route}`)
            continue
        }

        const states = []
        await interaction(name, null, async () => {
            await trigger.click()
            await page.waitForTimeout(700)
            states.push(await dialogState(page))
            await page.keyboard.press('Escape')
            await page.waitForTimeout(700)
            states.push(await dialogState(page))
        })

        const [open, closed] = states
        console.log(`     open=${JSON.stringify(open)} closed=${JSON.stringify(closed)}`)
        if (open && open.dialogs === 0) flag(name, 'dialog did not open')
        if (open && open.backdrops > 1) flag(name, `${open.backdrops} backdrops stacked while one dialog is open`)
        if (closed && closed.dialogs > 0) flag(name, 'Escape did not close the dialog')
    }

    // Command palette.
    await interaction('command-palette-open', '/admin/servers', async () => {
        await page.keyboard.press('Control+k')
        await page.waitForTimeout(800)
    })
    await page.keyboard.press('Escape').catch(() => {})

    // Theme lives in the account menu (avatar -> Theme -> Dark), not in a
    // standalone header toggle; the auth screens are the only place with one.
    // A whole-document repaint is expected here, blank frames are not.
    // The submenu entries are menuitemradio, not menuitem: a menuitem lookup
    // finds them in the DOM but never matches, and reads as a dead control.
    await interaction('theme-switch-to-dark', '/admin/nodes', async () => {
        await page.getByRole('button', { name: /open account menu/i }).click()
        await page.waitForTimeout(400)
        await page.getByRole('menuitem', { name: /^theme/i }).first().click()
        await page.waitForTimeout(400)
        await page.getByRole('menuitemradio', { name: /^dark$/i }).first().click()
        await page.waitForTimeout(900)
    })

    const theme = await page.evaluate(() => document.documentElement.className)
    console.log(`     documentElement.className = ${JSON.stringify(theme)}`)
    if (!/dark/.test(theme)) flag('theme-switch-to-dark', `dark mode did not apply (className=${JSON.stringify(theme)})`)

    // ---------- dark pass ----------
    // Re-walk every route with dark applied. A contrast failure or a hardcoded
    // light colour only shows up here, and it is invisible to the static pass.
    console.log('\n== dark pass ==')
    await page.evaluate(() => localStorage.setItem('theme', 'dark'))

    for (const route of ROUTES) {
        const before = errors.length
        await page.setViewportSize({ width: 1440, height: 900 })
        await page.goto(BASE + route, { waitUntil: 'networkidle' })
        await page.waitForTimeout(700)

        const cls = await page.evaluate(() => document.documentElement.className)
        if (!/dark/.test(cls)) flag(`dark${route}`, `theme did not stay dark (className=${JSON.stringify(cls)})`)

        const wide = await overflowAt(page, 1440)
        if (wide.over > 0) flag(`dark${route}`, `horizontal overflow ${wide.over}px at 1440`)

        // A pane still painting near-white under dark is an unthemed surface.
        const light = await page.evaluate(() => {
            const bg = el => getComputedStyle(el).backgroundColor
            const lum = c => {
                const m = c.match(/\d+/g)

                return m ? (+m[0] + +m[1] + +m[2]) / 3 : null
            }
            const body = lum(bg(document.body))
            const offenders = [...document.querySelectorAll('main *')]
                .filter(el => {
                    const r = el.getBoundingClientRect()
                    if (r.width < 200 || r.height < 60) return false
                    const l = lum(bg(el))

                    return l !== null && l > 220
                })
                .slice(0, 3)
                .map(el => `${el.tagName.toLowerCase()}.${String(el.className).split(' ').slice(0, 2).join('.')}`)

            return { body, offenders }
        })

        if (light.body !== null && light.body > 200) flag(`dark${route}`, `body background is near-white (${light.body}) under dark`)
        if (light.offenders.length) flag(`dark${route}`, `near-white surface(s) under dark: ${light.offenders.join(', ')}`)

        const newErrors = errors.slice(before)
        if (newErrors.length) flag(`dark${route}`, `console: ${newErrors.join(' | ')}`)

        await page.screenshot({ path: `${OUT}/dark${route.replace(/\//g, '_') || '_root'}.png` })
        console.log(`  dark ${route.padEnd(24)} bodyLum=${light.body} offenders=${light.offenders.length}`)
    }

    await browser.close()

    await writeFile(`${OUT}/sweep.json`, JSON.stringify({ findings, errors }, null, 2))
    console.log(`\n==== ${findings.length} finding(s) ====`)
    findings.forEach(f => console.log(`- ${f.where}: ${f.what}`))
}

await run()
