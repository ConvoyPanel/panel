/*
 * Frame-by-frame capture for UI interaction verification.
 *
 * A single still cannot show a sub-second glitch: a backdrop that flashes, a
 * layout that jumps one frame before settling, a skeleton that paints at the
 * wrong size, a drag whose ghost lags the cursor. This records the real frames
 * the compositor produced and lays them out as a contact sheet.
 *
 * CDP's screencast emits a frame only when the page actually changes, so the
 * frame count is itself a signal: a "simple" hover that produces 30 frames is
 * repainting more than it should, and two identical frames either side of a
 * different one is a flash.
 *
 * No ffmpeg. The contact sheet is composed in the browser from the captured
 * JPEGs and screenshotted, so the only dependency is the pinned Playwright the
 * dev kit already installs at /opt/sbx-e2e.
 */
import { mkdir, writeFile } from 'node:fs/promises'
import path from 'node:path'

/*
 * Record every frame the page paints while `action` runs.
 *
 * `settleAfter` keeps recording past the end of the action, because the glitch
 * worth catching is usually the settle, not the trigger.
 */
export const record = async (
    page,
    action,
    { quality = 70, maxWidth = 1440, maxHeight = 1000, settleAfter = 600 } = {}
) => {
    const client = await page.context().newCDPSession(page)
    const frames = []

    client.on('Page.screencastFrame', async ({ data, metadata, sessionId }) => {
        frames.push({ data, timestamp: metadata.timestamp })

        // Unacked frames stop the stream, so this must not throw on a page
        // that navigated out from under us mid-capture.
        try {
            await client.send('Page.screencastFrameAck', { sessionId })
        } catch {
            /* session gone; the frames already collected are still good */
        }
    })

    await client.send('Page.startScreencast', {
        format: 'jpeg',
        quality,
        maxWidth,
        maxHeight,
        everyNthFrame: 1,
    })

    // A frame at t0 with no change to trigger it, so a strip always has a
    // before-state to compare against.
    await page.waitForTimeout(120)

    let actionError = null

    try {
        await action()
    } catch (e) {
        actionError = e
    }

    await page.waitForTimeout(settleAfter)
    await client.send('Page.stopScreencast').catch(() => {})
    await client.detach().catch(() => {})

    if (actionError) throw actionError

    const t0 = frames.length ? frames[0].timestamp : 0

    return frames.map((f, i) => ({
        index: i,
        data: f.data,
        ms: Math.round((f.timestamp - t0) * 1000),
    }))
}

/*
 * Lay frames out as a labelled grid and screenshot it.
 *
 * Composed in a page rather than with an image tool so that reviewing a strip
 * costs one image instead of forty, and the elapsed-ms label sits on the frame
 * it belongs to.
 */
export const contactSheet = async (
    context,
    frames,
    { outPath, columns = 5, cellWidth = 320, title = '' }
) => {
    if (!frames.length) throw new Error(`no frames captured for ${title || outPath}`)

    await mkdir(path.dirname(outPath), { recursive: true })

    const page = await context.newPage()

    try {
        const html = `<!doctype html><meta charset="utf-8">
<style>
  :root { color-scheme: light }
  body { margin: 0; background: #0b0b0c; font: 12px ui-monospace, monospace; color: #e7e7ea }
  h1 { font: 600 14px ui-sans-serif, system-ui; margin: 12px 16px 4px }
  .meta { margin: 0 16px 12px; color: #9b9ba3 }
  .grid { display: grid; grid-template-columns: repeat(${columns}, ${cellWidth}px); gap: 8px; padding: 0 16px 16px }
  figure { margin: 0 }
  img { width: ${cellWidth}px; display: block; border: 1px solid #2a2a30; background: #fff }
  figcaption { padding: 3px 2px; color: #9b9ba3 }
  /* A frame that repeats its predecessor is the tell for a flash: mark the
     transitions so the eye lands on them first. */
  .jump figcaption { color: #ffd479 }
</style>
<h1>${escapeHtml(title)}</h1>
<div class="meta">${frames.length} frames · ${frames[frames.length - 1].ms} ms total</div>
<div class="grid">
${frames
    .map(
        f => `<figure><img src="data:image/jpeg;base64,${f.data}">
<figcaption>#${f.index} · +${f.ms}ms</figcaption></figure>`
    )
    .join('\n')}
</div>`

        await page.setViewportSize({
            width: columns * (cellWidth + 8) + 32,
            height: 1000,
        })
        await page.setContent(html, { waitUntil: 'load' })
        await page.screenshot({ path: outPath, fullPage: true })
    } finally {
        await page.close()
    }

    return outPath
}

/* Write the raw frames too, so a suspicious pair can be viewed at full size. */
export const dumpFrames = async (frames, dir, pick = []) => {
    await mkdir(dir, { recursive: true })

    const wanted = pick.length ? frames.filter(f => pick.includes(f.index)) : frames

    for (const f of wanted) {
        await writeFile(path.join(dir, `frame-${String(f.index).padStart(3, '0')}.jpg`), Buffer.from(f.data, 'base64'))
    }

    return wanted.length
}

const escapeHtml = s =>
    String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c])
