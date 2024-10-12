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
     * Run the database seeds.
     */
    public function run(ServerCreationService $service): void
    {
        $location = Location::factory()->create();
        $user = User::factory()->create();
        $node = Node::factory()->for($location)->create();

        Server::factory()->count(10)->create(function () use ($user, $node, $service) {
            $uuid = $service->generateUniqueUuidCombo();

            return [
                'uuid' => $uuid,
                'uuid_short' => substr($uuid, 0, 8),
                'user_id' => $user,
                'node_id' => $node,
                'cpu' => 2,
                'memory' => 2048 * 1024 * 1024,
                'disk' => 20 * 1024 * 1024 * 1024,
                'backup_limit' => 16,
                'snapshot_limit' => 16,
                'bandwidth_limit' => 100 * 1024 * 1024 * 1024,
            ];
        });
    }
}
