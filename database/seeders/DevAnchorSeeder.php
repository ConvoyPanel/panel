<?php

namespace Database\Seeders;

use App\Models\Node;
use Illuminate\Database\Seeder;

/**
 * Attach an already-installed Anchor agent to the dev node.
 *
 * Normally an agent is enrolled with `anchor enroll`, which POSTs a one-time
 * token to the panel and writes the config the panel hands back. That requires
 * the node to reach the panel — which a local dev panel on convoy.ddev.site
 * (127.0.0.1) never is. Enrollment only ever produces a config file, so for a
 * dev environment we skip the round trip and register both sides by hand.
 *
 * The values must match /etc/anchor/anchor.toml on the node exactly, or
 * AnchorAuthenticate rejects the `<uuid>.<secret>` bearer token:
 *
 *   ANCHOR_UUID    = installation_id
 *   ANCHOR_SECRET  = secret
 *   ANCHOR_URL     = public_url  (where the PANEL reaches the agent)
 *
 * The agent's own heartbeats will still fail — it cannot reach the panel — so
 * liveness comes from AnchorLivenessService probing the agent instead. That
 * means ANCHOR_URL must resolve from wherever PHP runs.
 *
 * Idempotent, keyed on the node's Anchor, so re-running never orphans a row.
 *
 * Run:  php artisan db:seed --class=DevAnchorSeeder
 */
class DevAnchorSeeder extends Seeder
{
    public function run(): void
    {
        $uuid = env('ANCHOR_UUID');
        $secret = env('ANCHOR_SECRET');
        $url = env('ANCHOR_URL');

        if (! $uuid || ! $secret || ! $url) {
            $this->command->warn(
                'DevAnchorSeeder skipped: set ANCHOR_UUID, ANCHOR_SECRET and ANCHOR_URL '
                .'in .env to match /etc/anchor/anchor.toml on the node.'
            );

            return;
        }

        $fqdn = env('PROXMOX_FQDN');
        $node = $fqdn ? Node::query()->where('fqdn', $fqdn)->first() : null;

        if ($node === null) {
            $this->command->warn('DevAnchorSeeder skipped: run DevNodeSeeder first.');

            return;
        }

        // The node *is* the installation now, so this writes onto the node
        // rather than creating a record to link to it.
        $node->update([
            'agent_uuid' => $uuid,
            'agent_secret' => $secret,
            'agent_public_url' => rtrim($url, '/'),
            // Enrollment is what proves the panel shares a secret with this
            // installation; the liveness probe will not vouch for an unenrolled
            // agent, so record that we did it out of band.
            'agent_enrolled_at' => $node->agent_enrolled_at ?? now(),
        ]);

        $this->command->info(
            "DevAnchorSeeder: agent attached to node #{$node->id} ({$node->agent_public_url})."
        );
    }
}
