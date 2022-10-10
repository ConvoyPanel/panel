<?php

namespace Database\Seeders;

use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\User;
use Illuminate\Database\Seeder;

class ServerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Server::factory()->count(10)->create(['user_id' => User::factory()->create(), 'node_id' => Node::factory()->create()]);
    }
}
