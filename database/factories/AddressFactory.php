<?php

namespace Database\Factories;

use App\Models\AddressBlock;
use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'address_block_id' => AddressBlock::factory(),
            'server_id' => null,
            'ip' => $this->faker->unique()->ipv4(),
            'prefix_length' => 32,
        ];
    }

    public function ipv6(): self
    {
        return $this->state(fn () => [
            'address_block_id' => AddressBlock::factory()->ipv6(),
            'ip' => $this->faker->unique()->ipv6(),
            'prefix_length' => 128,
        ]);
    }
}
