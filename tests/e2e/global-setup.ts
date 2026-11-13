import { execFileSync } from 'node:child_process'

/**
 * Resets the accounts, settings and images the specs rely on. Runs `artisan`
 * directly, which is why the suite runs where the panel does (the ddev web
 * container, or the CI runner). Set E2E_SKIP_SEED=1 to point the suite at a
 * panel you've seeded yourself.
 */
const globalSetup = () => {
    if (process.env.E2E_SKIP_SEED) return

    execFileSync(
        'php',
        [
            'artisan',
            'db:seed',
            '--class=E2eSeeder',
            '--force',
            '--no-interaction',
        ],
        { stdio: 'inherit' }
    )
}

export default globalSetup
