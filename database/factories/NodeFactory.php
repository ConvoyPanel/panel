<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class NodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'display_name' => $this->faker->words(2, true),
            'name' => $this->faker->word(),
            'verify_tls' => true,
            'fqdn' => $this->faker->domainName(),
            'token_id' => $this->faker->word(),
            'token_secret' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'port' => 8006,
            'socket_count' => 2,
            'core_count' => 16,
            'cpu_count' => 32,
            'memory' => 68719476736, // 64 gb
            'memory_overallocate' => 0,
            'anchor_id' => null,
        ];
    }
}
