<?php

namespace App\Data\Server\Proxmox\Snapshot;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

class SnapshotData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $parentName,
        public ?Carbon $createdAt,
        public bool $includesRam,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(
            name: $raw['name'],
            description: $raw['description'] ?? null,
            parentName: $raw['parent'] ?? null,
            createdAt: isset($raw['snaptime']) ? Carbon::createFromTimestamp($raw['snaptime']) : null,
            includesRam: $raw['vmstate'] ?? false,
        );
    }
}
