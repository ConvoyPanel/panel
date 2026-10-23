import { Page, expect } from '@playwright/test'

// Seeded by database/seeders/E2eSeeder.php.
export const admin = {
    email: 'admin@e2e.test',
    password: 'password',
}

export const signInAsAdmin = async (page: Page) => {
    await page.goto('/auth/login')
    await page.getByLabel('Email').fill(admin.email)
    await page.getByLabel('Password').fill(admin.password)
    await page.getByRole('button', { name: 'Sign in' }).click()

    // The login page does a full navigation away once the session is set.
    await expect(page).not.toHaveURL(/\/auth\/login/)
}
