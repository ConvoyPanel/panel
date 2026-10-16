/** Mirrors database/seeders/E2eSeeder.php. */
export const PASSWORD = 'password'

export const users = {
    superadmin: 'admin@e2e.test',
    user: 'user@e2e.test',
    guest: 'guest@e2e.test',
} as const

export type Account = keyof typeof users

export const storageState = (account: Account) =>
    `tests/e2e/.auth/${account}.json`

/** Prefix the seeder clears, so roles a run creates don't pile up. */
export const ROLE_PREFIX = 'E2E '

export const UPLOAD_GROUP = 'E2E Uploads'

export const UPLOAD_IMAGE = 'E2E Upload Target'
