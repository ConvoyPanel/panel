<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerDiskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'storage_id' => Storage::factory(),
            // Bytes in; StorageSizeCast stores MiB (matches ServerFactory.disk).
            'size' => 20 * 1024 * 1024 * 1024,
            'interface' => null,
            'is_primary' => true,
            'disk_index' => 0,
        ];
    }

    public function secondary(): static
    {
        return $this->state(fn () => ['is_primary' => false, 'disk_index' => 1]);
    }
}
