import { expect, test } from '@playwright/test'

import { signInAsAdmin } from '../support/auth'

test.beforeEach(async ({ page }) => {
    await signInAsAdmin(page)
})

// Deleting a template used to happen on the first click. The DELETE is
// answered in the browser, so the seeded template stays for the other specs.
test('asks before deleting a template and sends the delete once', async ({
    page,
}) => {
    const deletes: string[] = []
    let answer!: () => void
    const answered = new Promise<void>(resolve => (answer = resolve))

    await page.route(
        '**/api/admin/nodes/*/template-groups/*/templates/*',
        async route => {
            if (route.request().method() !== 'DELETE') return route.fallback()

            deletes.push(route.request().url())
            await answered
            await route.fulfill({ status: 204 })
        }
    )

    await page.goto('/admin/nodes')
    await page.getByRole('link', { name: 'e2e-node' }).click()
    await expect(page).toHaveURL(/\/admin\/nodes\/\d+$/)
    await page.goto(`${page.url()}/templates`)

    const template = page.getByText('E2E Debian 12', { exact: true })
    const menu = page
        .locator('div')
        .filter({ has: template })
        .filter({ has: page.getByRole('button', { name: 'More actions' }) })
        .last()
        .getByRole('button', { name: 'More actions' })
    const dialog = page.getByRole('dialog', { name: 'Delete E2E Debian 12?' })

    await menu.click()
    await page.getByRole('menuitem', { name: 'Delete' }).click()
    await dialog.waitFor({ state: 'attached' })
    await dialog.getByRole('button', { name: 'Cancel' }).click()
    await expect(dialog).toHaveCount(0)
    expect(deletes).toHaveLength(0)

    await menu.click()
    await page.getByRole('menuitem', { name: 'Delete' }).click()
    await dialog.waitFor({ state: 'attached' })
    await dialog.getByRole('button', { name: 'Delete', exact: true }).dblclick()
    await expect(dialog.getByRole('button', { name: 'Cancel' })).toBeDisabled()

    answer()
    await expect(template).toHaveCount(0)
    await expect(dialog).toHaveCount(0)
    expect(deletes).toHaveLength(1)
})
