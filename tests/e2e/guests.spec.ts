import { Browser, Page, expect, test } from '@playwright/test'

import { api } from './support/api'
import { signIn } from './support/auth'
import { PASSWORD, storageState, users } from './support/users'

test.use({ storageState: storageState('superadmin') })

const LOCKED_OUT =
    'This account cannot sign in. Contact the person who shared a server with you.'

/** Flips Admin → Settings → Sharing → Guest accounts, through the form. */
const setGuestAccounts = async (page: Page, allowed: boolean) => {
    await page.goto('/admin/settings/permissions')

    const toggle = page.getByRole('switch', {
        name: 'Let customers share with new people',
    })
    await expect(toggle).toBeVisible()

    if ((await toggle.getAttribute('aria-checked')) !== String(allowed)) {
        await toggle.click()
    }
    await expect(toggle).toHaveAttribute('aria-checked', String(allowed))

    await page.getByRole('button', { name: 'Save changes' }).click()
    await expect(page.getByText('Sharing settings updated')).toBeVisible()
}

const guestSession = async (browser: Browser) => {
    const context = await browser.newContext({
        storageState: { cookies: [], origins: [] },
    })
    return context.newPage()
}

test('a guest cannot sign in while guest accounts are off', async ({
    page,
    browser,
}) => {
    await setGuestAccounts(page, false)

    const guest = await guestSession(browser)
    await guest.goto('/auth/login')
    await guest.getByLabel('Email').fill(users.guest)
    await guest.getByLabel('Password').fill(PASSWORD)
    await guest.getByRole('button', { name: 'Sign in', exact: true }).click()

    await expect(
        guest.getByText('Invalid email or password').first()
    ).toBeVisible()
    await expect(guest).toHaveURL(/\/auth\/login/)
})

test('turning guest accounts off signs a signed-in guest out', async ({
    page,
    browser,
}) => {
    await setGuestAccounts(page, true)

    const guest = await guestSession(browser)
    await signIn(guest, 'guest')
    expect((await api(guest).get('/api/client/user')).status()).toBe(200)

    await setGuestAccounts(page, false)

    // The session cookie is still there; every request it makes is refused.
    const refused = await api(guest).get('/api/client/user')
    expect(refused.status()).toBe(403)
    expect((await refused.json()).message).toBe(LOCKED_OUT)

    await guest.reload()
    await expect(guest).toHaveURL(/\/auth\/login/)
})

test('a standard account is unaffected by the switch', async ({
    page,
    browser,
}) => {
    await setGuestAccounts(page, false)

    const user = await browser.newContext({
        storageState: storageState('user'),
    })
    const userPage = await user.newPage()
    expect((await api(userPage).get('/api/client/user')).status()).toBe(200)
    await user.close()
})
