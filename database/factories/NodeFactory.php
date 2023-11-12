<?php

namespace Database\Factories;

use Convoy\Models\Location;
use Convoy\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

class NodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Node::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'name' => $this->faker->word(),
            'cluster' => 'proxmox',
            'fqdn' => $this->faker->word(),
            'token_id' => $this->faker->word(),
            'secret' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'port' => 8006,
            'memory' => 68719476736, // 64 gb
            'memory_overallocate' => 0,
            'disk' => 137438953472, // 128 gb
            'disk_overallocate' => 0,
            'vm_storage' => 'local',
            'backup_storage' => 'local',
            'iso_storage' => 'local',
            'network' => 'vmbr0',
            'coterm_id' => null,
        ];
    }
}
