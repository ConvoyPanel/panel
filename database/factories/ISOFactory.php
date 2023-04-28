<?php

namespace Database\Factories;

use Convoy\Models\ISO;
use Convoy\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ISO>
 */
class ISOFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ISO::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid(),
            'node_id' => Node::factory(),
            'is_successful' => $this->faker->boolean(),
            'name' => $this->faker->name(),
            'file_name' => "{$this->faker->name()}.iso",
            'size' => $this->faker->randomNumber(),
            'hidden' => $this->faker->boolean(),
            'completed_at' => $this->faker->dateTime(),
        ];
    }
}
