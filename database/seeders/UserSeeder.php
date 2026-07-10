<?php

namespace Database\Seeders;

use App\Enums\Api\ApiKeyType;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\Api\CreateAccountTokenService;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed a testable account: ensures a user exists and gives them something to
     * look at on the account "Security" page — SSH keychain keys and personal API
     * tokens. Reuses the first existing user by default (so it targets the account
     * you're logged in as); on an empty database it creates a demo login. Idempotent
     * (skips rows that already exist by name), so it's safe to re-run.
     *
     * Override the target user from the CLI (email or id), e.g.:
     *   ddev exec sh -c 'SEED_USER=you@example.com php artisan db:seed --class=UserSeeder'
     */
    public function run(CreateAccountTokenService $tokens): void
    {
        $user = $this->resolveUser();

        $this->seedSshKeys($user);
        $this->seedApiTokens($user, $tokens);

        $this->command->info("Seeded SSH keys + API tokens for {$user->email}.");
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
