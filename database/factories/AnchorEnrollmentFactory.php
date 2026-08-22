<?php

namespace Database\Factories;

use App\Enums\Anchor\AnchorMode;
use App\Models\AnchorEnrollment;
use App\Support\Anchor\AnchorProtocol;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AnchorEnrollment> */
class AnchorEnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => 'pve-new.example.com',
            'mode' => AnchorMode::AGENT,
            'secret' => Str::random(64),
            'enrolled_at' => now(),
            'last_seen_at' => now(),
            'version' => '0.1.0-alpha.1',
            'protocol_min' => AnchorProtocol::VERSION,
            'protocol_max' => AnchorProtocol::VERSION,
            'capabilities' => ['console.qemu.vnc', 'console.qemu.terminal'],
            'reported_facts' => [
                'hostname' => 'pve-new.example.com',
                'pve_node_name' => 'pve-new',
                'cpu' => ['sockets' => 2, 'cores' => 32, 'threads' => 64],
                'memory_bytes' => 549755813888,
                'observed_source_ip' => '10.0.0.11',
            ],
        ];
    }

    public function relay(): static
    {
        return $this->state(fn () => [
            'mode' => AnchorMode::RELAY,
            'capabilities' => ['console.relay'],
        ]);
    }
}
