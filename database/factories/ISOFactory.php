<?php

namespace Database\Factories;

use App\Models\ISO;
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
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->name(),
            'file_name' => "{$this->faker->unique()->word()}.iso",
            'url' => 'https://example.invalid/'.$this->faker->unique()->word().'.iso',
            'path' => null,
            'sha256' => hash('sha256', $this->faker->unique()->word()),
            'size' => $this->faker->randomNumber(),
            'hidden' => false,
        ];
    }

    /** An ISO the panel is hosting rather than one the operator links to. */
    public function hosted(): static
    {
        return $this->state(fn () => [
            'url' => null,
            'path' => 'iso-'.hash('sha256', $this->faker->unique()->word()).'.iso',
        ]);
    }
}
