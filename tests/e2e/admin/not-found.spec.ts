import { expect, test } from '@playwright/test'

import { signInAsAdmin } from '../support/auth'

test.beforeEach(async ({ page }) => {
    await signInAsAdmin(page)
})

// Opening a deleted record used to show "Something went wrong" with Laravel's
// "No query results for model [...]" (servers), or crash outright (nodes, users).
for (const { path, record } of [
    { path: '/servers/deadbeef', record: 'Server' },
    { path: '/admin/servers/00000000-0000-4000-8000-00000000dead', record: 'Server' },
    { path: '/admin/nodes/999999999', record: 'Node' },
    { path: '/admin/users/999999999', record: 'User' },
]) {
    test(`says a missing ${record.toLowerCase()} is gone (${path})`, async ({ page }) => {
        await page.goto(path)

        await expect(page.getByRole('heading', { name: `${record} not found` })).toBeVisible()
        await expect(page.getByText('No query results')).toHaveCount(0)
        await expect(page.getByRole('button', { name: 'Go Back' })).toBeVisible()
    })
}
