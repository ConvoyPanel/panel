<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClusterFactory extends Factory
{
    /** A real, fingerprinted cluster. Use `standalone()` for a singleton scope. */
    public function definition(): array
    {
        return [
            'fingerprint' => Str::upper(implode(':', str_split(bin2hex(random_bytes(16)), 2))),
            'name' => $this->faker->word(),
            'member_names' => null,
        ];
    }

    public function standalone(): static
    {
        return $this->state([
            'fingerprint' => null,
            'name' => null,
        ]);
    }
}
