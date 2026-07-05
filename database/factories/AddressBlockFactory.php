<?php

namespace Database\Factories;

use App\Enums\Network\AddressVersion;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AddressBlock>
 */
class AddressBlockFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'address_block_group_id' => AddressBlockGroup::factory(),
            'name' => $this->faker->word(),
            'description' => null,
            'version' => AddressVersion::IPv4,
            'base_ip' => $this->faker->ipv4(),
            'gateway' => $this->faker->ipv4(),
            'mac_address' => null,
            'prefix_length_from' => 24,
            'prefix_length_to' => 32,
        ];
    }

    public function ipv6(): self
    {
        return $this->state(fn () => [
            'version' => AddressVersion::IPv6,
            'base_ip' => $this->faker->ipv6(),
            'gateway' => $this->faker->ipv6(),
            'prefix_length_from' => 48,
            'prefix_length_to' => 128,
        ]);
    }
}
