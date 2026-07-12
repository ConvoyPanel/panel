<?php

namespace App\Extensions\Spatie\Data\Proxmox\Casts;

use App\Extensions\Spatie\Data\Proxmox\ProxmoxPropertyCast;

/**
 * Proxmox expresses a NIC `rate` in *decimal* megabytes per second — the PVE API
 * documents `net[n].rate` as "Rate limit in mbps (megabytes per second)", and its
 * traffic-control setup is 10^6-based, NOT binary MiB. Internally we store bytes/s.
 *
 * (Do not route this through App\Support\ByteUnit: that enum is deliberately binary
 * 1024-based for disk/memory sizes, which is a different quantity.)
 */
class RateLimitCast implements ProxmoxPropertyCast
{
    /** Proxmox's NIC rate unit: one decimal megabyte per second. */
    private const BYTES_PER_MEGABYTE = 1_000_000;

    public function parse(string $value): int
    {
        return (int) round(((float) $value) * self::BYTES_PER_MEGABYTE);
    }

    public function emit(mixed $value): ?string
    {
        // bytes/s -> decimal MB/s; (string) trims a whole number to "100" and keeps
        // fractional rates like "1.5". Proxmox accepts a floating point number.
        return (string) ((int) $value / self::BYTES_PER_MEGABYTE);
    }
}
