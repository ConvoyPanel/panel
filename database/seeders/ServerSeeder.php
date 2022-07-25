<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Server;
use App\Models\User;
use App\Models\Node;

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