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
            // What a storage holds is read off PVE's list, so a factory sets
            // the list rather than seven flags derived from it.
            'pve_content' => 'images,rootdir,vztmpl,backup,iso,snippets,import',
        ];
    }
}
