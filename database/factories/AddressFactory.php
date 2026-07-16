<?php

namespace Database\Factories;

use App\Enums\Network\AddressState;
use App\Models\Address;
use App\Models\AddressBlock;
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
            'state' => AddressState::Available,
        ];
    }

    /**
     * Keep state consistent with server_id: an address created with a server attached is 'assigned'
     * unless a state was set explicitly.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Address $address) {
            if ($address->server_id !== null && $address->state === AddressState::Available) {
                $address->state = AddressState::Assigned;
            }
        });
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
