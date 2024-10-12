<?php

namespace Database\Factories;

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
        $type = $this->faker->randomElement(['ipv4', 'ipv6']);
        
        return [
            'type' => $type,
            'address' => $type === 'ipv4' ? $this->faker->ipv4 : $this->faker->ipv6,
            'cidr' => $this->faker->numberBetween(0, 128),
            'gateway' => $type === 'ipv4' ? $this->faker->ipv4 : $this->faker->ipv6,
            'mac_address' => $this->faker->randomElement([null, $this->faker->macAddress]),
        ];
    }
}
