<?php

namespace Database\Factories;

use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

class NetworkInterfaceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'node_id' => Node::factory(),
            'name' => 'vmbr'.$this->faker->unique()->numberBetween(0, 4094),
            'description' => null,
            'is_vlan_aware' => false,
            'vlan_tag' => null,
        ];
    }

    /**
     * A trunk. Pass a tag to give it a default that untagged servers inherit;
     * without one it is a pure trunk where every server carries its own.
     */
    public function trunk(?int $vlanTag = null): static
    {
        return $this->state([
            'is_vlan_aware' => true,
            'vlan_tag' => $vlanTag,
        ]);
    }
}
