<?php

namespace Database\Factories;

use App\Models\Location;
use App\Support\Anchor\AnchorProtocol;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            // No agent by default: that is the v4 shape, and the one a test
            // has to opt out of rather than into.
            'agent_uuid' => null,
        ];
    }

    /** A node whose host is running an enrolled, healthy agent. */
    public function withAgent(): static
    {
        return $this->state(fn () => [
            'agent_uuid' => (string) Str::uuid(),
            'agent_secret' => Str::random(64),
            'agent_public_url' => 'https://'.$this->faker->domainName(),
            'agent_enrolled_at' => now(),
            'agent_last_seen_at' => now(),
            'agent_version' => '0.1.0-alpha.1',
            'agent_protocol_min' => AnchorProtocol::VERSION,
            'agent_protocol_max' => AnchorProtocol::VERSION,
            'agent_capabilities' => ['console.qemu.vnc', 'console.qemu.terminal'],
        ]);
    }
}
