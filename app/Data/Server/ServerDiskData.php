<?php

namespace App\Data\Server;

use App\Models\ServerDisk;
use Spatie\LaravelData\Data;

class ServerDiskData extends Data
{
    public function __construct(
        public int $id,
        public int $storageId,
        public ?string $storageName,
        public int $size,
        public ?string $interface,
        public bool $isPrimary,
        public int $diskIndex,
    ) {}

    public static function fromModel(ServerDisk $disk): self
    {
        return new self(
            id: $disk->id,
            storageId: $disk->storage_id,
            storageName: $disk->storage->name,
            size: (int) $disk->size,
            interface: $disk->interface,
            isPrimary: (bool) $disk->is_primary,
            diskIndex: $disk->disk_index,
        );
    }
}
