<?php

namespace Database\Factories;

use App\Models\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;
use function rand;

class StorageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nickname' => rand(0, 3) === 3 ? $this->faker->words() : null,
            'description' => rand(0, 3) === 3 ? $this->faker->sentence() : null,
            'name' => $this->faker->word(),
            'size' => rand(60, 100) * 1024 * 1024 * 1024,
            'is_shareable' => rand(0, 1) === 1,
            'has_kvm' => rand(0, 1) === 1,
            'has_lxc' => rand(0, 1) === 1,
            'has_lxc_templates' => rand(0, 1) === 1,
            'has_backups' => rand(0, 1) === 1,
            'has_isos' => rand(0, 1) === 1,
            'has_snippets' => rand(0, 1) === 1,
        ];
    }
}
