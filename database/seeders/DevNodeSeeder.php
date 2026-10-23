<?php

namespace Database\Seeders;

use Convoy\Models\Location;
use Convoy\Models\Node;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Seed a single, real Proxmox node from environment variables.
 *
 * A port of 5.x's DevNodeSeeder, for a dev box that needs a live node to test
 * against -- NOT for production. Credentials come from the gitignored .env, so
 * nothing sensitive is committed:
 *
 *   PROXMOX_FQDN          host address, e.g. 10.0.0.10 or pve.example.com   (required)
 *   PROXMOX_TOKEN_ID      API token id, e.g. root@pam!convoy                (required)
 *   PROXMOX_TOKEN_SECRET  API token secret (UUID)                           (required)
 *   PROXMOX_PORT          API port (default 8006)
 *   PROXMOX_VERIFY_TLS    verify the node's TLS cert (default false)
 *   PROXMOX_NODE_NAME     PVE cluster node name (default: fqdn's first label)
 *
 * The 4.x Node splits what 5.x kept in one field: `cluster` is the PVE node name
 * interpolated into every API path (`/nodes/{cluster}/...`), while `name` is a
 * free-form label. Get `cluster` wrong and every call fails with
 * "hostname lookup '<name>' failed".
 *
 * Idempotent: an existing node with the same fqdn is left alone, so re-running
 * after migrate:fresh never creates duplicates.
 *
 * Run:  ddev artisan db:seed --class=DevNodeSeeder
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

        $cluster = env('PROXMOX_NODE_NAME') ?: explode('.', $fqdn)[0];

        $location = Location::query()->firstOrCreate(
            ['short_code' => 'dev'],
            ['description' => 'Dev Proxmox'],
        );

        $verifyTls = filter_var(env('PROXMOX_VERIFY_TLS', false), FILTER_VALIDATE_BOOLEAN);
        $port = (int) env('PROXMOX_PORT', 8006);

        // Advertised capacity drives the admin Capacity card and every
        // overallocation check, so read it off the host rather than inventing it.
        // Falls back to the factory's numbers when the node cannot be reached --
        // the seeder still has to work offline.
        $capacity = $this->probeCapacity($fqdn, $port, $cluster, $tokenId, $tokenSecret, $verifyTls);

        // The factory supplies resource defaults; only the connection details and
        // storage names come from the environment.
        $node = Node::factory()->for($location)->create([
            'name' => $cluster,
            'cluster' => $cluster,
            'fqdn' => $fqdn,
            'port' => $port,
            'verify_tls' => $verifyTls,
            'token_id' => $tokenId,
            'secret' => $tokenSecret, // encrypted by the model cast on save
            'vm_storage' => env('PROXMOX_VM_STORAGE', 'local'),
            'backup_storage' => env('PROXMOX_BACKUP_STORAGE', 'local'),
            'iso_storage' => env('PROXMOX_ISO_STORAGE', 'local'),
            'network' => env('PROXMOX_NETWORK', 'vmbr0'),
        ] + $capacity);

        $this->command->info("DevNodeSeeder: created node #{$node->id} for {$fqdn}:{$node->port} (cluster {$cluster}).");
    }

    /**
     * Real memory and disk totals for the node, as a partial attribute array.
     *
     * Disk is the configured `vm_storage`'s total, which is the pool servers are
     * actually built on -- summing every storage would double-count a host where
     * one directory backs several entries. Returns an empty array when the node is
     * unreachable, leaving the factory defaults in place.
     */
    private function probeCapacity(
        string $fqdn,
        int $port,
        string $cluster,
        string $tokenId,
        string $tokenSecret,
        bool $verifyTls,
    ): array {
        $vmStorage = env('PROXMOX_VM_STORAGE', 'local');

        try {
            $client = Http::withOptions(['verify' => $verifyTls])
                ->withHeaders(['Authorization' => "PVEAPIToken={$tokenId}={$tokenSecret}"])
                ->timeout(15)
                ->baseUrl("https://{$fqdn}:{$port}");

            $memory = $client->get("/api2/json/nodes/{$cluster}/status")->json('data.memory.total');

            $disk = collect($client->get("/api2/json/nodes/{$cluster}/storage")->json('data') ?? [])
                ->firstWhere('storage', $vmStorage)['total'] ?? null;
        } catch (Throwable $e) {
            $this->command->warn("DevNodeSeeder: capacity probe failed ({$e->getMessage()}); using factory defaults.");

            return [];
        }

        return array_filter([
            'memory' => $memory ? (int) $memory : null,
            'disk' => $disk ? (int) $disk : null,
        ]);
    }
}
