<?php

namespace Database\Factories;

use App\Enums\Anchor\AnchorMode;
use App\Models\Anchor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Anchor> */
class AnchorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->words(2, true),
            'mode' => AnchorMode::AGENT,
            'public_url' => 'https://'.$this->faker->domainName(),
            'secret' => Str::random(64),
            /*
             * Approved by default: a factory Anchor stands in for one an admin
             * created by hand, which is approved by construction. The pending
             * state is a self-registration artefact and has its own state.
             */
            'approved_at' => now(),
        ];
    }

    /** A machine that introduced itself and is waiting to be let in. */
    public function pending(): static
    {
        return $this->state(fn () => [
            'public_url' => null,
            'approved_at' => null,
            'enrolled_at' => now(),
            'last_seen_at' => now(),
            'protocol_min' => Anchor::PROTOCOL_VERSION,
            'protocol_max' => Anchor::PROTOCOL_VERSION,
            'reported_facts' => ['hostname' => 'pve-new.example.com'],
        ]);
    }

    public function enrolled(): static
    {
        return $this->state(fn () => [
            'enrolled_at' => now(),
            'last_seen_at' => now(),
            'version' => '0.1.0-alpha.1',
            'protocol_min' => Anchor::PROTOCOL_VERSION,
            'protocol_max' => Anchor::PROTOCOL_VERSION,
            'capabilities' => ['console.qemu.vnc', 'console.qemu.terminal'],
        ]);
    }
}
