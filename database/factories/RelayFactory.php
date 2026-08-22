<?php

namespace Database\Factories;

use App\Models\Relay;
use App\Support\Anchor\AnchorProtocol;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Relay> */
class RelayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->words(2, true),
            'public_url' => 'https://'.$this->faker->domainName(),
            'secret' => Str::random(64),
        ];
    }

    public function enrolled(): static
    {
        return $this->state(fn () => [
            'enrolled_at' => now(),
            'last_seen_at' => now(),
            'version' => '0.1.0-alpha.1',
            'protocol_min' => AnchorProtocol::VERSION,
            'protocol_max' => AnchorProtocol::VERSION,
            'capabilities' => ['console.relay'],
        ]);
    }
}
