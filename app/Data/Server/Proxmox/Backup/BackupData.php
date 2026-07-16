<?php

namespace App\Data\Server\Proxmox\Backup;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

class BackupData extends Data
{
    public function __construct(
        public string $volumeId,
        public string $filename,
        public string $format,
        public int $size,
        public CarbonImmutable $createdAt,
        public ?int $vmid,
        public ?string $parent,
        public ?string $notes,
        public ?bool $isProtected,
        public ?int $used,
        public ?string $encrypted,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(
            volumeId: Arr::get($raw, 'volid'),
            filename: basename(Arr::get($raw, 'volid')),
            format: Arr::get($raw, 'format'),
            size: Arr::get($raw, 'size'),
            createdAt: CarbonImmutable::createFromTimestampUTC(Arr::get($raw, 'ctime')),
            vmid: Arr::get($raw, 'vmid'),
            parent: Arr::get($raw, 'parent'),
            notes: Arr::get($raw, 'notes'),
            isProtected: Arr::get($raw, 'protected'),
            used: Arr::get($raw, 'used'),
            encrypted: Arr::get($raw, 'encrypted'),
        );
    }
}
