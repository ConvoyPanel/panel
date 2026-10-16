import { expect, test } from '@playwright/test'

import { signInAsAdmin } from '../support/auth'

test.beforeEach(async ({ page }) => {
    await signInAsAdmin(page)
})

// A failed install used to be a dead end. The seeded server (E2eSeeder) is in
// install_failed, and the retry has to go through the reinstall endpoint.
test('offers to retry a failed install', async ({ page }) => {
    await page.goto('/servers/e2e00000')

    await expect(
        page.getByRole('heading', { name: 'Install failed' })
    ).toBeVisible()

    await page.getByLabel('Template Group').click()
    await page.getByRole('option', { name: 'E2E Linux' }).click()
    await page.getByLabel('Template', { exact: true }).click()
    await page.getByRole('option', { name: 'E2E Debian 12' }).click()
    await page.getByLabel('System OS Password').fill('Rt-Aa1!bcdefgh9')

    // The seeded node isn't a real Proxmox host, so answer the reinstall here
    // rather than queue a build that could only fail.
    let body: Record<string, unknown> | undefined
    await page.route('**/settings/reinstall', async route => {
        body = route.request().postDataJSON()
        await route.fulfill({ status: 204 })
    })

    await page.getByRole('button', { name: 'Retry Installation' }).click()
    await page.getByRole('button', { name: 'Confirm' }).click()

    await expect(page.getByRole('heading', { name: 'Installing' })).toBeVisible()
    expect(body).toMatchObject({ account_password: 'Rt-Aa1!bcdefgh9' })
    expect(body?.template_uuid).toBeTruthy()

    // Pretend the install has now finished. The Installing screen polls, so it
    // has to move on by itself -- nobody reloads the page.
    await page.route('**/api/client/servers/e2e00000', async route => {
        const response = await route.fetch()
        const json = await response.json()
        json.data.status = null
        await route.fulfill({ response, json })
    })

    await expect(page.getByRole('heading', { name: 'Installing' })).toBeHidden({
        timeout: 15_000,
    })
    await expect(
        page.getByRole('heading', { name: 'Install failed' })
    ).toBeHidden()
})
