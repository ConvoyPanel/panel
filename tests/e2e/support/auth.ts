import { Page, expect } from '@playwright/test'

import { Account, PASSWORD, users } from './users'

export const signIn = async (page: Page, account: Account) => {
    await page.goto('/auth/login')
    await page.getByLabel('Email').fill(users[account])
    await page.getByLabel('Password').fill(PASSWORD)
    await page.getByRole('button', { name: 'Sign in', exact: true }).click()
    await expect(page).not.toHaveURL(/\/auth\//)
}
