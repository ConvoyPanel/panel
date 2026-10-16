import { Page, expect, test } from '@playwright/test'

import { api, getData } from './support/api'
import { REGISTRY_URL } from './support/registry'
import { storageState } from './support/users'

test.use({ storageState: storageState('superadmin') })
test.describe.configure({ mode: 'serial' })

interface ImportResult {
    slug: string
    imageGroupUuid: string
    imageDefinitionUuid: string
    version: string
    created: boolean
}

const definitionUrl = (result: ImportResult) =>
    `/api/admin/image-groups/${result.imageGroupUuid}/images/${result.imageDefinitionUuid}`

const definitionOf = (page: Page, result: ImportResult) =>
    getData<{ hardware: Record<string, unknown> }>(page, definitionUrl(result))

const versionsOf = (page: Page, result: ImportResult) =>
    getData<unknown[]>(page, `${definitionUrl(result)}/versions`)

const openCatalogue = async (page: Page) => {
    await page.goto('/admin/images')
    await page.getByRole('button', { name: 'Browse catalogue' }).first().click()

    const sheet = page.getByRole('dialog')
    await expect(sheet.getByText('Convoy E2E Catalogue')).toBeVisible()

    return sheet
}

const row = (sheet: ReturnType<Page['getByRole']>, display: string) =>
    sheet.locator('[data-slot="item"]').filter({ hasText: display })

/** Clicks Import on a catalogue row and returns what the panel made of it. */
const importFromSheet = async (page: Page, display: string) => {
    const sheet = await openCatalogue(page)

    const response = page.waitForResponse(
        r =>
            r.url().endsWith('/api/admin/images/registry/imports') &&
            r.request().method() === 'POST'
    )
    await row(sheet, display).getByRole('button', { name: 'Import' }).click()

    const [result] = (await (await response).json()).data as ImportResult[]

    return { sheet, result }
}

test('reads the fixture catalogue, not the CDN', async ({ page }) => {
    const catalogue = await getData<{ url: string }>(
        page,
        '/api/admin/images/registry?refresh=1'
    )

    expect(
        catalogue.url,
        `Set IMAGE_REGISTRY_URL=${REGISTRY_URL} in the panel's environment (see AGENTS.md)`
    ).toBe(REGISTRY_URL)
})

test('importing an entry once creates one version', async ({ page }) => {
    const { sheet, result } = await importFromSheet(page, 'Debian 13')

    expect(result.created).toBe(true)
    await expect(
        page.getByText(`Imported build ${result.version}`)
    ).toBeVisible()

    const debian = row(sheet, 'Debian 13')
    await expect(debian.getByText('Imported', { exact: true })).toBeVisible()
    await expect(debian.getByRole('button', { name: 'Import' })).toBeDisabled()

    expect(await versionsOf(page, result)).toHaveLength(1)
})

test('importing the same build again adds nothing', async ({ page }) => {
    const response = await api(page).post(
        '/api/admin/images/registry/imports',
        {
            templates: ['debian-13-amd64'],
        }
    )
    expect(response.ok()).toBe(true)

    const [result] = (await response.json()).data as ImportResult[]
    expect(result.created).toBe(false)
    expect(await versionsOf(page, result)).toHaveLength(1)

    const sheet = await openCatalogue(page)
    await expect(
        row(sheet, 'Debian 13').getByRole('button', { name: 'Import' })
    ).toBeDisabled()
})

test('Windows Server 2025 is imported with a v2.0 TPM', async ({ page }) => {
    const { result } = await importFromSheet(page, 'Windows Server 2025')
    expect(result.created).toBe(true)

    expect((await definitionOf(page, result)).hardware.tpm).toBe('v2.0')
})
