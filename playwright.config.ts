import { defineConfig, devices } from '@playwright/test'

/**
 * End-to-end tests against a running panel seeded with E2eSeeder.
 *
 * The panel is not started from here: CI serves it from the workspace
 * container, and locally it is whatever E2E_BASE_URL points at. The default is
 * 127.0.0.1:8000 rather than localhost:8000 because it is in Sanctum's default
 * stateful domains, so the session cookie from logging in is honoured.
 */
export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8000',
        trace: 'retain-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
})
