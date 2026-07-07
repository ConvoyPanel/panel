<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Node;
use Illuminate\Database\Seeder;

/**
 * Seed a single, real Proxmox node from environment variables.
 *
 * Intended for a disposable sandbox where an agent needs a live node to test
 * against — NOT for production. The credentials come from the project .env
 * (which is mounted into the sandbox), so nothing sensitive is committed:
 *
 *   PROXMOX_FQDN          host address, e.g. 10.0.0.10 or pve.example.com   (required)
 *   PROXMOX_TOKEN_ID      API token id, e.g. root@pam!convoy                (required)
 *   PROXMOX_TOKEN_SECRET  API token secret (UUID)                           (required)
 *   PROXMOX_PORT          API port (default 8006)
 *   PROXMOX_VERIFY_TLS    verify the node's TLS cert (default false)
 *
 * Idempotent: if a node with the same fqdn already exists it is left as-is, so
 * re-running (e.g. after migrate:fresh) never creates duplicates.
 *
 * Run:  php artisan db:seed --class=DevNodeSeeder
 */
class DevNodeSeeder extends Seeder
{
    public function run(): void
    {
        $fqdn = env('PROXMOX_FQDN');
        $tokenId = env('PROXMOX_TOKEN_ID');
        $tokenSecret = env('PROXMOX_TOKEN_SECRET');

        if (! $fqdn || ! $tokenId || ! $tokenSecret) {
            $this->command->warn(
                'DevNodeSeeder skipped: set PROXMOX_FQDN, PROXMOX_TOKEN_ID and '
                .'PROXMOX_TOKEN_SECRET in .env first.'
            );

            return;
        }

        if ($existing = Node::query()->where('fqdn', $fqdn)->first()) {
            $this->command->info("DevNodeSeeder: node for {$fqdn} already exists (#{$existing->id}).");

            return;
        }

        // Reuse a location if one exists, otherwise create one with factory defaults.
        $location = Location::query()->first() ?? Location::factory()->create();

        // Factory supplies sane resource defaults (cpu/memory/etc.); we override
        // only the connection details from the environment.
        $node = Node::factory()->for($location)->create([
            'display_name' => 'Dev Proxmox',
            'name' => 'dev-node',
            'fqdn' => $fqdn,
            'port' => (int) env('PROXMOX_PORT', 8006),
            'verify_tls' => filter_var(env('PROXMOX_VERIFY_TLS', false), FILTER_VALIDATE_BOOLEAN),
            'token_id' => $tokenId,
            'token_secret' => $tokenSecret, // encrypted by the model cast on save
        ]);

        $this->command->info("DevNodeSeeder: created node #{$node->id} for {$fqdn}:{$node->port}.");
    }
}
