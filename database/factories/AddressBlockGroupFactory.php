<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AddressBlockGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => rand(0, 3) === 3 ? $this->faker->sentence() : null,
        ];
    }
}
