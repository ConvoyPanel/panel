import { test as setup } from '@playwright/test'

import { signIn } from './support/auth'
import { storageState } from './support/users'

for (const account of ['superadmin', 'user'] as const) {
    setup(`sign in as ${account}`, async ({ page }) => {
        await signIn(page, account)
        await page.context().storageState({ path: storageState(account) })
    })
}
