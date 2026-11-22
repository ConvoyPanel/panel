<?php

namespace Database\Factories;

use App\Models\Provisioner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Provisioner>
 */
class ProvisionerFactory extends Factory
{
    private static int $nameSequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => sprintf('Provisioner %04d', ++self::$nameSequence),
            'description' => null,
            'script' => "#!/bin/sh\necho hello\n",
            'fields' => [],
        ];
    }

    /**
     * @param  array<int, array{key: string, label: string, required: bool}>  $fields
     */
    public function withFields(array $fields): self
    {
        return $this->state(['fields' => $fields]);
    }
}
