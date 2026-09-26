<?php

namespace Database\Seeders;

use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Models\Template;
use Convoy\Models\TemplateGroup;
use Convoy\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed the fixed records the Playwright suite in tests/e2e logs in with and
 * clicks through -- NOT for production.
 *
 * Nothing here talks to Proxmox: the node is a factory row, so only flows that
 * stay inside the panel's own database can be exercised against it. The names
 * are constants the specs look for, so change them in both places.
 *
 * Run:  php artisan migrate:fresh --seed --seeder=E2eSeeder
 */
class E2eSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'E2E Admin',
            'email' => 'admin@e2e.test',
            'root_admin' => true,
        ]); // password: "password", from the factory

        $node = Node::factory()->for(Location::factory())->create([
            'name' => 'e2e-node',
            'fqdn' => 'e2e-node.test',
        ]);

        $group = TemplateGroup::create([
            'node_id' => $node->id,
            'name' => 'E2E Linux',
            'hidden' => false,
        ]);

        Template::create([
            'template_group_id' => $group->id,
            'name' => 'E2E Debian 12',
            'vmid' => 9000,
            'hidden' => false,
        ]);
    }
}
