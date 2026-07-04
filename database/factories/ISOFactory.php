<?php

namespace Database\Factories;

use App\Models\ISO;
use App\Models\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ISO>
 */
class ISOFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'storage_id' => Storage::factory(),
            'uuid' => $this->faker->uuid(),
            'is_successful' => true,
            'name' => $this->faker->name(),
            'file_name' => "{$this->faker->unique()->word()}.iso",
            'size' => $this->faker->randomNumber(),
            'hidden' => false,
            'completed_at' => $this->faker->dateTime(),
        ];
    }
}
