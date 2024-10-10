<?php

namespace Database\Factories;

use Convoy\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

class NodeFactory extends Factory
{
    protected $model = Node::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'cluster' => 'proxmox',
            'verify_tls' => true,
            'fqdn' => $this->faker->word(),
            'token_id' => $this->faker->word(),
            'secret' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'port' => 8006,
            'memory' => 68719476736, // 64 gb
            'memory_overallocate' => 0,
            'network' => 'vmbr0',
            'coterm_id' => null,
        ];
    }
}
