<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use function rand;

class StorageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'display_name' => rand(0, 3) === 3 ? $this->faker->words(2, true) : null,
            'description' => rand(0, 3) === 3 ? $this->faker->sentence() : null,
            'name' => $this->faker->word(),
            'size' => rand(60, 100) * 1024 * 1024 * 1024,
            'stores_kvm' => true,
            'stores_lxc' => true,
            'stores_lxc_templates' => true,
            'stores_backups' => true,
            'stores_iso' => true,
            'stores_snippets' => true,
        ];
    }
}
