import { expect, test } from '@playwright/test'

import { signInAsAdmin } from '../support/auth'

test.beforeEach(async ({ page }) => {
    await signInAsAdmin(page)
})

// #169: the node select holds its id as a string, and once the template-groups
// hook started requiring a number it never fetched, leaving both pickers empty.
test('offers the chosen node templates when creating a server', async ({
    page,
}) => {
    await page.goto('/admin/servers')
    await page.getByRole('button', { name: 'Create Server' }).click()

    const modal = page.getByRole('dialog')

    await modal.getByLabel('Node').click()
    await page.getByRole('option', { name: /e2e-node/ }).click()

    await modal.getByLabel('Template Group').click()
    await page.getByRole('option', { name: 'E2E Linux' }).click()

    await modal.getByLabel('Template', { exact: true }).click()
    await expect(
        page.getByRole('option', { name: 'E2E Debian 12' })
    ).toBeVisible()
})
