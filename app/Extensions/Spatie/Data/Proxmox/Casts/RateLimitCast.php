<?php

namespace App\Extensions\Spatie\Data\Proxmox\Casts;

use App\Extensions\Spatie\Data\Proxmox\ProxmoxPropertyCast;
use App\Support\ByteUnit;

/**
 * Proxmox expresses NIC rate limits in MiB/s; internally we store bytes/s.
 */
class RateLimitCast implements ProxmoxPropertyCast
{
    public function parse(string $value): int
    {
        return ByteUnit::Mebibytes->toBytes((float) $value);
    }

    public function emit(mixed $value): ?string
    {
        return (string) ByteUnit::Mebibytes->fromBytes((int) $value);
    }
}
