<?php

namespace Database\Factories;

use App\Models\NetworkInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

class VlanFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'network_interface_id' => NetworkInterface::factory(),
            'tag' => $this->faker->unique()->numberBetween(1, 4094),
            'name' => $this->faker->word(),
            'description' => null,
        ];
    }
}
