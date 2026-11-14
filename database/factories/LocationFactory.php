<?php

namespace Database\Factories;

use Convoy\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Unique in the database, and faker's word list is short enough
            // that two locations in one test sometimes drew the same word.
            'short_code' => $this->faker->word().'-'.Str::lower(Str::random(6)),
            'description' => $this->faker->sentence(),
        ];
    }
}
