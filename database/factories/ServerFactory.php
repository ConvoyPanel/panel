<?php

namespace Database\Factories;

use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->firstName(),
            'user_id' => User::factory(),
            'node_id' => Node::factory(),
            'vmid' => rand(100, 5000),
        ];
    }
}
