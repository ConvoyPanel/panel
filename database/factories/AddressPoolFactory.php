<?php

namespace Database\Factories;

use Convoy\Models\AddressPool;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AddressPoolFactory extends Factory
{
    protected $model = AddressPool::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
