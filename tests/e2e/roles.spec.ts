import { Page, expect, test } from '@playwright/test'

import { api, assignRole } from './support/api'
import { ROLE_PREFIX, storageState, users } from './support/users'

test.describe.configure({ mode: 'serial' })

/** What the limited role grants, and the admin nav items that should follow from it. */
const PERMISSIONS = [
    'overview.read',
    'image-groups.read',
    'users.read',
    'users.manage',
]
const VISIBLE = ['Dashboard', 'Images', 'Users', 'Roles']
const HIDDEN = [
    'Locations',
    'Nodes',
    'Servers',
    'ISOs',
    'API Tokens',
    'Audit Log',
    'Settings',
]

const ROLE_NAME = `${ROLE_PREFIX}Image support ${Date.now()}`

const adminNav = (page: Page) => page.getByRole('navigation').first()

test.describe('as the superadmin', () => {
    test.use({ storageState: storageState('superadmin') })

    test('creates a limited role and assigns it to the plain user', async ({
        page,
    }) => {
        await page.goto('/admin/roles')
        await page.getByRole('button', { name: 'Add role' }).click()

        const dialog = page.getByRole('dialog')
        await dialog.getByLabel('Name').fill(ROLE_NAME)
        for (const permission of PERMISSIONS) {
            await dialog
                .locator(`label[for="permission-${permission}"]`)
                .click()
        }

        const created = page.waitForResponse(
            r =>
                r.request().method() === 'POST' &&
                r.url().endsWith('/api/admin/admin-roles')
        )
        await dialog.getByRole('button', { name: 'Add role' }).click()

        const response = await created
        expect(response.status()).toBe(201)
        const body = await response.json()
        const role = body.data ?? body
        await expect(page.getByText('Role created')).toBeVisible()

        const assigned = await assignRole(page, users.user, role.id)
        expect(assigned.ok(), await assigned.text()).toBe(true)
    })
})

test.describe('as the plain user holding the limited role', () => {
    test.use({ storageState: storageState('user') })

    test('sees only the admin areas the role grants', async ({ page }) => {
        await page.goto('/admin')
        await expect(page).toHaveURL(/\/admin/)

        const nav = adminNav(page)
        for (const item of VISIBLE) {
            await expect(
                nav.getByRole('link', { name: item, exact: true })
            ).toBeVisible()
        }
        for (const item of HIDDEN) {
            await expect(
                nav.getByRole('link', { name: item, exact: true })
            ).toHaveCount(0)
        }
    })

    test('can read what the role grants and gets 403 everywhere else', async ({
        page,
    }) => {
        await page.goto('/admin')
        const client = api(page)

        expect((await client.get('/api/admin/image-groups')).status()).toBe(200)
        expect((await client.get('/api/admin/users')).status()).toBe(200)

        for (const url of [
            '/api/admin/nodes',
            '/api/admin/locations',
            '/api/admin/servers',
            '/api/admin/settings/permissions',
        ]) {
            expect((await client.get(url)).status(), url).toBe(403)
        }

        // image-groups.read, not .manage
        expect(
            (
                await client.post('/api/admin/image-groups', { name: 'Nope' })
            ).status()
        ).toBe(403)
    })

    test('cannot author roles', async ({ page }) => {
        await page.goto('/admin/roles')
        await expect(page.getByText(ROLE_NAME)).toBeVisible()
        await expect(
            page.getByRole('button', { name: 'Add role' })
        ).toHaveCount(0)

        const response = await api(page).post('/api/admin/admin-roles', {
            name: `${ROLE_PREFIX}Self-made`,
            description: null,
            permissions: ['settings.manage'],
        })
        expect(response.status()).toBe(403)
        expect((await response.json()).message).toBe(
            'Only a full administrator can author roles.'
        )
    })
})

test.describe('as the plain user once the role is taken away', () => {
    test.use({ storageState: storageState('user') })

    test('is kept out of the admin area', async ({ page, browser }) => {
        const admin = await browser.newContext({
            storageState: storageState('superadmin'),
        })
        const adminPage = await admin.newPage()
        await adminPage.goto('/admin')

        const revoked = await assignRole(adminPage, users.user, null)
        expect(revoked.ok(), await revoked.text()).toBe(true)
        await admin.close()

        await page.goto('/admin')
        await expect(page).not.toHaveURL(/\/admin/)
        expect((await api(page).get('/api/admin/image-groups')).status()).toBe(
            403
        )
    })
})
