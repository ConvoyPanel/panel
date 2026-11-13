/*
 * Drag helpers.
 *
 * Two different problems:
 *
 *  - Raw pointer handlers (the avatar cropper) only need a down/move/up with
 *    enough intermediate points that the handler sees real deltas. A single
 *    jump from A to B produces one move event and hides any lag.
 *  - dnd-kit's PointerSensor has an activation constraint, so a drag that
 *    starts and ends in one step never activates at all: the test "passes"
 *    while nothing moved. It needs a small priming move past the threshold,
 *    then steps, then a settle before release.
 *
 * Steps are deliberate rather than Playwright's `steps` option because the
 * frame recorder wants time to pass between them: an instant interpolation
 * paints one frame and defeats the point of recording.
 */

export const dragSmooth = async (page, from, to, { steps = 24, holdMs = 12, settleMs = 260 } = {}) => {
    await page.mouse.move(from.x, from.y)
    await page.mouse.down()
    await page.waitForTimeout(40)

    for (let i = 1; i <= steps; i++) {
        const t = i / steps
        // Ease in-out, so the middle of the gesture moves fastest; a dropped
        // frame shows up as a visible gap rather than an even stutter.
        const e = t < 0.5 ? 2 * t * t : 1 - (-2 * t + 2) ** 2 / 2
        await page.mouse.move(from.x + (to.x - from.x) * e, from.y + (to.y - from.y) * e)
        await page.waitForTimeout(holdMs)
    }

    await page.waitForTimeout(settleMs)
    await page.mouse.up()
    await page.waitForTimeout(settleMs)
}

/* dnd-kit: prime past the activation constraint before the real travel. */
export const dragSortable = async (page, handle, to, opts = {}) => {
    const box = await handle.boundingBox()
    if (!box) throw new Error('drag handle has no bounding box')

    const from = { x: box.x + box.width / 2, y: box.y + box.height / 2 }

    await page.mouse.move(from.x, from.y)
    await page.mouse.down()
    // Past the default 8px activation distance, in small steps.
    for (const d of [3, 6, 10, 14]) {
        await page.mouse.move(from.x, from.y + d)
        await page.waitForTimeout(25)
    }

    await dragSmoothTail(page, { x: from.x, y: from.y + 14 }, to, opts)
}

const dragSmoothTail = async (page, from, to, { steps = 20, holdMs = 14, settleMs = 300 } = {}) => {
    for (let i = 1; i <= steps; i++) {
        const t = i / steps
        await page.mouse.move(from.x + (to.x - from.x) * t, from.y + (to.y - from.y) * t)
        await page.waitForTimeout(holdMs)
    }

    await page.waitForTimeout(settleMs)
    await page.mouse.up()
    await page.waitForTimeout(settleMs)
}
