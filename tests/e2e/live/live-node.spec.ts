import { expect, test } from '@playwright/test'

import { storageState } from '../support/users'

/**
 * Specs that need a real Proxmox node: seed one with
 * `ddev artisan db:seed --class=DevNodeSeeder`, export the same PROXMOX_*
 * variables to the runner, and run `ddev npm run test:e2e -- --grep @live`.
 * The panel reads those credentials from .env, but the runner can't see them
 * unless they are exported, so this skips without them rather than failing
 * against a panel with no node.
 */
const hasLiveNode = Boolean(
    process.env.PROXMOX_FQDN &&
    process.env.PROXMOX_TOKEN_ID &&
    process.env.PROXMOX_TOKEN_SECRET
)

test.describe('against a live Proxmox node', { tag: '@live' }, () => {
    test.skip(
        !hasLiveNode,
        'PROXMOX_FQDN, PROXMOX_TOKEN_ID and PROXMOX_TOKEN_SECRET are not set'
    )
    test.use({ storageState: storageState('superadmin') })

    test('the seeded node is reachable from the panel', async ({ page }) => {
        await page.goto('/admin/nodes')
        await expect(
            page
                .getByRole('link', {
                    name: process.env.PROXMOX_NODE_NAME ?? /.+/,
                })
                .first()
        ).toBeVisible()
    })

    // TODO: Admin → Unmanaged Guests lists a VM created on the node outside Convoy;
    // adopting it assigns an owner and it shows up on that user's dashboard.
    test.fixme('adopts an unmanaged guest', async () => {})

    // TODO: Admin → Servers → a server → Migration offers the other cluster members;
    // migrating moves the guest, and a failed migration leaves no guest behind on the destination.
    test.fixme('migrates a server to another node in its cluster', async () => {})

    // TODO: the owner shares a server with an unknown email (creating a guest, with the
    // switch on) and with the plain user as a sub-user; each sees exactly the permissions granted,
    // and turning guest accounts off takes the guest's access away but keeps the share.
    test.fixme('shares a server with a sub-user and a guest', async () => {})
})
