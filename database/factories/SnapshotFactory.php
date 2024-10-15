<?php

namespace Database\Factories;

use App\Models\Snapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

class SnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'description' => rand(0, 3) === 3 ? $this->faker->sentence() : null,
            'size' => rand(3, 10) * 1024 * 1024 * 1024,
            'completed_at' => $this->faker->dateTime(),
        ];
    }
}
