<?php

namespace App\Extensions\Spatie\Data\Proxmox\Casts;

use App\Extensions\Spatie\Data\Proxmox\ProxmoxPropertyCast;

/**
 * Proxmox expresses NIC rate limits in MiB/s; internally we store bytes/s.
 */
class RateLimitCast implements ProxmoxPropertyCast
{
    private const BYTES_PER_MIB = 1024 * 1024;

    public function parse(string $value): int
    {
        return (int) floor(((float) $value) * self::BYTES_PER_MIB);
    }

    public function emit(mixed $value): ?string
    {
        return (string) ($value / self::BYTES_PER_MIB);
    }
}
