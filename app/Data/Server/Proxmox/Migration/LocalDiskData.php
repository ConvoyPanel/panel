<?php

namespace App\Data\Server\Proxmox\Migration;

use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

/**
 * A disk that lives on node-local storage, so migrating it copies bytes.
 */
class LocalDiskData extends Data
{
    public function __construct(
        public readonly string $volumeId,
        public readonly int $size,
        public readonly bool $isCdrom,
        public readonly bool $isUnused,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(
            volumeId: (string) Arr::get($raw, 'volid'),
            size: (int) Arr::get($raw, 'size', 0),
            isCdrom: (bool) Arr::get($raw, 'cdrom', false),
            isUnused: (bool) Arr::get($raw, 'is_unused', false),
        );
    }
}
