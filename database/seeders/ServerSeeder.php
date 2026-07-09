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
     */
    public function run(ServerCreationService $service): void
    {
        $user = User::query()->first() ?? User::factory()->create();
        $location = Location::query()->first() ?? Location::factory()->create();
        $node = Node::query()->first() ?? Node::factory()->for($location)->create();

        Server::factory()->count(10)->create(function () use ($user, $node, $service) {
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
}
