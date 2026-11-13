import { defineConfig, devices } from '@playwright/test'

import { REGISTRY_PORT } from './tests/e2e/support/registry'

/**
 * End-to-end suite. Runs inside the ddev web container (`ddev npm run test:e2e`)
 * against the panel that container serves; see AGENTS.md for the one-time setup.
 */
const baseURL =
    process.env.E2E_BASE_URL ??
    process.env.DDEV_PRIMARY_URL ??
    'https://convoy-pr168.ddev.site'

export default defineConfig({
    testDir: './tests/e2e',
    outputDir: './tests/e2e/.results',
    globalSetup: './tests/e2e/global-setup.ts',
    // The specs share one database and one global guest switch, so they run one at a time.
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI
        ? [
              ['github'],
              ['html', { open: 'never', outputFolder: 'tests/e2e/.report' }],
          ]
        : [
              ['list'],
              ['html', { open: 'never', outputFolder: 'tests/e2e/.report' }],
          ],
    use: {
        baseURL,
        // ddev's certificates are signed by mkcert's local CA, which the container's Chromium doesn't trust.
        ignoreHTTPSErrors: true,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    // The image catalogue fixture, read by the panel through IMAGE_REGISTRY_URL.
    webServer: {
        command: 'node tests/e2e/support/registry-server.mjs',
        port: REGISTRY_PORT,
        reuseExistingServer: !process.env.CI,
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.ts/ },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
            dependencies: ['setup'],
        },
    ],
})
