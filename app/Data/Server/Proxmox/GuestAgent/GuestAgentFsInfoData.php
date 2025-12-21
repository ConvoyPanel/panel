<?php

namespace App\Data\Server\Proxmox\GuestAgent;

use Spatie\LaravelData\Data;
use Illuminate\Support\Arr;

class GuestAgentFsInfoData extends Data
{
    public function __construct(
        public string $name,
        public string $mountPoint,
        public string $type,
        public int $usedBytes,
        public int $totalBytes,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(
            name: Arr::get($raw, 'name', ''),
            mountPoint: Arr::get($raw, 'mountpoint', ''),
            type: Arr::get($raw, 'type', ''),
            usedBytes: (int) Arr::get($raw, 'used-bytes', 0),
            totalBytes: (int) Arr::get($raw, 'total-bytes', 0),
        );
    }
}
