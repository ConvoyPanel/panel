<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Convoy\Models\Server;
use Convoy\Models\User;
use Convoy\Models\Node;

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