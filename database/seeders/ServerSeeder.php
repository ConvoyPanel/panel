<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use App\Services\Servers\ServerCreationService;
use Illuminate\Database\Seeder;

class ServerSeeder extends Seeder
{
    /**
     * Seed a handful of servers. Reuses the first existing user/node/location so
     * running this against a live dev database gives the account you're logged in
     * as something to look at; only falls back to factories on an empty database.
     *
     * Override the owner (and count) from the CLI, e.g.:
     *   ddev exec sh -c 'SEED_SERVER_USER=you@example.com php artisan db:seed --class=ServerSeeder'
     *   ddev exec sh -c 'SEED_SERVER_USER=3 SEED_SERVER_COUNT=5 php artisan db:seed --class=ServerSeeder'
     * SEED_SERVER_USER accepts an email or a user id.
     */
    public function run(ServerCreationService $service): void
    {
        $user = $this->resolveUser();
        $location = Location::query()->first() ?? Location::factory()->create();
        $node = Node::query()->first() ?? Node::factory()->for($location)->create();

        $count = (int) (env('SEED_SERVER_COUNT') ?: 10);

        Server::factory()->count($count)->create(function () use ($user, $node, $service) {
            $uuid = $service->generateUniqueUuidCombo();

            return [
                'uuid' => $uuid,
                'uuid_short' => substr($uuid, 0, 8),
                'user_id' => $user->id,
                'node_id' => $node->id,
                'cpu' => 2,
                'memory' => 2048 * 1024 * 1024,
                'disk' => 20 * 1024 * 1024 * 1024,
            ];
        });
    }

    /**
     * Resolve the owner for the seeded servers: SEED_SERVER_USER (email or id)
     * when set, otherwise the first existing user, falling back to a new one.
     */
    private function resolveUser(): User
    {
        $override = env('SEED_SERVER_USER');

        if ($override) {
            $user = str_contains((string) $override, '@')
                ? User::query()->where('email', $override)->first()
                : User::query()->find($override);

            if (! $user) {
                throw new \RuntimeException("SEED_SERVER_USER \"{$override}\" did not match any user.");
            }

            return $user;
        }

        return User::query()->first() ?? User::factory()->create();
    }
}
