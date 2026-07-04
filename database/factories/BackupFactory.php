<?php

namespace Database\Factories;

use App\Models\Backup;
use App\Models\Server;
use App\Models\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Backup>
 */
class BackupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'storage_id' => Storage::factory(),
            'uuid' => $this->faker->uuid(),
            'is_locked' => $this->faker->boolean(),
            'name' => $this->faker->word(),
            'file_name' => $this->faker->word(),
            'size' => $this->faker->randomNumber(),
            'completed_at' => $this->faker->dateTime(),
        ];
    }
}
