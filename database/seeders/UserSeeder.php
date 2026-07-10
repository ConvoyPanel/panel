<?php

namespace Database\Seeders;

use App\Enums\Api\ApiKeyType;
use App\Enums\Network\AddressVersion;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\PersonalAccessToken;
use App\Models\Server;
use App\Models\User;
use App\Services\Api\CreateAccountTokenService;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed a testable account: ensures a user exists and gives them something to
     * look at on the account "Security" page — SSH keychain keys and personal API
     * tokens — plus IP addresses on their first server (the one server-settings
     * surface that's DB-backed; the others read live Proxmox and can't be seeded).
     * Reuses the first existing user by default (so it targets the account you're
     * logged in as); on an empty database it creates a demo login. Idempotent, so
     * it's safe to re-run.
     *
     * Override the target user from the CLI (email or id), e.g.:
     *   ddev exec sh -c 'SEED_USER=you@example.com php artisan db:seed --class=UserSeeder'
     */
    public function run(CreateAccountTokenService $tokens): void
    {
        $user = $this->resolveUser();

        $this->seedSshKeys($user);
        $this->seedApiTokens($user, $tokens);
        $this->seedServerAddresses($user);

        $this->command->info("Seeded account data for {$user->email}.");
    }

    /** A few realistic public keys across algorithms. */
    private function seedSshKeys(User $user): void
    {
        $keys = [
            'MacBook Pro' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAINmtig1BvfmCSRapAUEi4iEbS/B4LTZLUJr+e4mWZ9Nb eric@macbook',
            'CI Deploy Key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDvZOUERdjPy/vcYlE87nBZ7SMAZcKXeEsBzucAVmSsMsI5pgdER8qWKf66/W1Bl84tX2wUG2f2v/CbWkuoTCnuM7zqtI/NzGArS4U7cqBKlovFcnnfyn5rsKdVxhaEbUIaXKwkU5MwAyuD6OEtSwbZyYKwMl86qTtEhEqfaDq1x4Q691XT9hzWbgdUVo//3agK8xZtdK0ZlnCnzRNTtDjAA0HYLv76r7QOAst0CHfa+bmw212pkFOoaXybDGME54MRHtu24c8NKmB4DH+fmJ+oXCBEO9WGBG6GllQY2wooIf1mRqnFoi6vWDc42RL67x6XVOjR9GFTyLEdSSrZGyTB deploy@ci',
            'Bastion (ops)' => 'ecdsa-sha2-nistp256 AAAAE2VjZHNhLXNoYTItbmlzdHAyNTYAAAAIbmlzdHAyNTYAAABBBNpxGav8RYv4eCf4KENGxPNF3opuoEM0et9E62kiEB+1vhkLVdK8b2QBexIB/0GejuRHB9T42GJyEj7emfFpc6Q= ops@bastion',
        ];

        foreach ($keys as $name => $publicKey) {
            $user->sshKeys()->firstOrCreate(
                ['name' => $name],
                ['public_key' => $publicKey],
            );
        }
    }

    /** Personal access tokens with a spread of scopes. */
    private function seedApiTokens(User $user, CreateAccountTokenService $tokens): void
    {
        $seeds = [
            'Terraform Provider' => ['servers:read', 'servers:write'],
            'Monitoring (read-only)' => ['servers:read'],
            'CI Pipeline' => ['servers:write'],
        ];

        foreach ($seeds as $name => $abilities) {
            $exists = PersonalAccessToken::query()
                ->where('tokenable_type', $user->getMorphClass())
                ->where('tokenable_id', $user->getKey())
                ->where('type', ApiKeyType::ACCOUNT)
                ->where('name', $name)
                ->exists();

            if (! $exists) {
                $tokens->handle($user, $name, $abilities);
            }
        }
    }

    /**
     * Assign a few public IPv4 addresses to the user's first server so the
     * Networking → Addresses card has content. This is the only server-settings
     * surface backed by the DB (IPAM); SSH keys, DNS, disks and boot order all
     * read live Proxmox config, so they only populate against a reachable node.
     */
    private function seedServerAddresses(User $user): void
    {
        $server = Server::query()->where('user_id', $user->getKey())->first();

        if (! $server) {
            $this->command->warn('  No server for this user — skipping IP addresses (run ServerSeeder first).');

            return;
        }

        if ($server->addresses()->exists()) {
            return;
        }

        // TEST-NET-3 (203.0.113.0/24) — a documentation range, safe for fixtures.
        $block = AddressBlock::factory()->create([
            'name' => 'Public IPv4',
            'version' => AddressVersion::IPv4,
            'base_ip' => '203.0.113.0',
            'gateway' => '203.0.113.1',
            'prefix_length_from' => 24,
            'prefix_length_to' => 32,
        ]);

        foreach (['203.0.113.10', '203.0.113.11', '203.0.113.12'] as $ip) {
            Address::factory()->create([
                'address_block_id' => $block->getKey(),
                'server_id' => $server->getKey(),
                'ip' => $ip,
                'prefix_length' => 24,
            ]);
        }
    }

    /**
     * Resolve the target user: SEED_USER (email or id) when set, otherwise the
     * first existing user, falling back to creating a demo login on an empty DB.
     */
    private function resolveUser(): User
    {
        $override = env('SEED_USER');

        if ($override) {
            $user = str_contains((string) $override, '@')
                ? User::query()->where('email', $override)->first()
                : User::query()->find($override);

            if (! $user) {
                throw new \RuntimeException("SEED_USER \"{$override}\" did not match any user.");
            }

            return $user;
        }

        $existing = User::query()->first();

        if ($existing) {
            return $existing;
        }

        $demo = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@convoy.test',
            'root_admin' => true,
        ]);

        $this->command->info('Created demo login: demo@convoy.test / password');

        return $demo;
    }
}
