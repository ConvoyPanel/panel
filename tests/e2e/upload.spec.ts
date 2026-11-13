import { Page, Request, expect, test } from '@playwright/test'
import { writeFile } from 'node:fs/promises'

import { UPLOAD_GROUP, UPLOAD_IMAGE, storageState } from './support/users'

test.use({ storageState: storageState('superadmin') })

const MiB = 1024 * 1024
const SIZE = 48 * MiB
/** The panel's default `ARTIFACTS_UPLOAD_CHUNK_BYTES`, so 48 MiB is three chunks. */
const CHUNK = 16 * MiB

const isChunk = (request: Request) =>
    request.method() === 'PUT' &&
    /\/api\/admin\/images\/uploads\/[^/]+$/.test(request.url())

const isFinalize = (request: Request) =>
    request.method() === 'POST' &&
    /\/api\/admin\/images\/uploads\/[^/]+\/finalize$/.test(request.url())

/** A disk image of `SIZE` bytes. `fill` keeps each test's file (and its sha256) distinct. */
const diskImage = async (path: string, fill: number) => {
    await writeFile(path, Buffer.alloc(SIZE, fill))
    return path
}

/** Records the `Upload-Offset` of every chunk the page sends, whatever happens to it. */
const recordOffsets = (page: Page) => {
    const offsets: number[] = []
    page.on('request', request => {
        if (isChunk(request))
            offsets.push(Number(request.headers()['upload-offset']))
    })
    return offsets
}

const openVersions = async (page: Page) => {
    await page.goto('/admin/images')
    await page.getByText(UPLOAD_GROUP, { exact: true }).click()

    await page
        .getByRole('dialog')
        .locator('div')
        .filter({ has: page.getByRole('heading', { name: UPLOAD_IMAGE }) })
        .getByRole('button', { name: 'Open image actions' })
        .last()
        .click()
    await page.getByRole('menuitem', { name: 'Versions' }).click()

    return page.getByRole('dialog')
}

test('a 48 MiB image goes up in three chunks, then is finalized', async ({
    page,
}, testInfo) => {
    const offsets = recordOffsets(page)
    const dialog = await openVersions(page)

    const opened = page.waitForResponse(
        r =>
            r.request().method() === 'POST' &&
            r.url().endsWith('/api/admin/images/uploads')
    )
    const finalized = page.waitForResponse(r => isFinalize(r.request()))

    await dialog
        .locator('input[type="file"]')
        .setInputFiles(await diskImage(testInfo.outputPath('disk.raw'), 1))

    expect((await (await opened).json()).chunk_size).toBe(CHUNK)

    const result = await finalized
    expect(result.ok()).toBe(true)
    expect(await result.json()).toMatchObject({ size: SIZE, format: 'raw' })

    expect(offsets).toEqual([0, CHUNK, 2 * CHUNK])
    await expect(dialog.getByText(/Hosted by Convoy/)).toBeVisible()
})

test('an interrupted upload resumes from the offset the server holds', async ({
    page,
}, testInfo) => {
    const offsets = recordOffsets(page)

    // Drop the second chunk on the floor: the server keeps the first and never sees the second.
    let dropped = false
    await page.route(/\/api\/admin\/images\/uploads\/[^/]+$/, async route => {
        if (isChunk(route.request()) && !dropped && offsets.length === 2) {
            dropped = true
            return route.abort('connectionreset')
        }
        return route.fallback()
    })

    const dialog = await openVersions(page)
    await dialog
        .locator('input[type="file"]')
        .setInputFiles(await diskImage(testInfo.outputPath('disk.raw'), 2))

    const resume = dialog.getByRole('button', { name: 'Resume' })
    await expect(resume).toBeVisible()
    expect(offsets).toEqual([0, CHUNK])

    const probed = page.waitForResponse(
        r =>
            r.request().method() === 'GET' &&
            /\/api\/admin\/images\/uploads\/[^/]+$/.test(r.url())
    )
    const finalized = page.waitForResponse(r => isFinalize(r.request()))

    await resume.click()

    expect((await (await probed).json()).offset).toBe(CHUNK)
    expect((await finalized).ok()).toBe(true)

    // The dropped chunk is sent again from the server's offset, not restarted from zero.
    expect(offsets).toEqual([0, CHUNK, CHUNK, 2 * CHUNK])
    await expect(dialog.getByText(/Hosted by Convoy/)).toBeVisible()
})
