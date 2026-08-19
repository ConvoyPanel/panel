<?php

namespace Database\Factories;

use App\Models\ServerPreset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerPreset>
 */
class ServerPresetFactory extends Factory
{
    private static int $nameSequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => sprintf('Preset %06d', ++self::$nameSequence),
            'description' => $this->faker->sentence(),
            // Node-scoped settings are left out on purpose: the default preset
            // has to be creatable without a node existing first.
            'settings' => [
                'cpu' => 2,
                'memory' => 2048,
                'disk' => 20480,
                'backup_count' => 2,
                'backup_size' => 40960,
                'addresses_ipv4_count' => 1,
                'addresses_ipv6_count' => 0,
            ],
        ];
    }
}
