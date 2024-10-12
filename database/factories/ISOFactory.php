<?php

namespace Database\Factories;

use App\Models\ISO;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ISO>
 */
class ISOFactory extends Factory
{
    protected $model = ISO::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'is_successful' => $this->faker->boolean(),
            'name' => $this->faker->name(),
            'file_name' => "{$this->faker->name()}.iso",
            'size' => $this->faker->randomNumber(),
            'hidden' => $this->faker->boolean(),
            'completed_at' => $this->faker->dateTime(),
        ];
    }
}
