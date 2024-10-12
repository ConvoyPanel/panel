<?php

namespace Database\Factories;

use App\Models\Backup;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Backup>
 */
class BackupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Backup::class;

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
            'is_locked' => $this->faker->boolean(),
            'name' => $this->faker->word(),
            'file_name' => $this->faker->word(),
            'size' => $this->faker->randomNumber(),
            'completed_at' => $this->faker->dateTime(),
        ];
    }
}
