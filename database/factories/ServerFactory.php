<?php

namespace Database\Factories;

use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\User;
use Convoy\Services\Servers\ServerCreationService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\App;

class ServerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Server::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $uuid = App::make(ServerCreationService::class)->generateUniqueUuidCombo();

        return [
            'uuid' => $uuid,
            'uuid_short' => substr($uuid, 0, 8),
            'user_id' => User::factory(),
            'node_id' => Node::factory(),
            'hostname' => $this->faker->domainName(),
            'name' => $this->faker->word(),
            'vmid' => rand(100, 5000),
            'cpu' => 2,
            'memory' => 17179869184,
            'disk' => 17179869184,
        ];
    }
}
