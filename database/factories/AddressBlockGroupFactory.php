<?php

namespace Database\Factories;

use App\Models\AddressBlockGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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
