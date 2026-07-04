<?php

namespace App\Extensions\Spatie\Data\Proxmox\Casts;

use App\Extensions\Spatie\Data\Proxmox\ProxmoxPropertyCast;

/**
 * Proxmox encodes booleans as the strings "1" and "0" rather than
 * "true"/"false", so a plain bool cast can't be inferred from the type alone.
 */
class PveBooleanCast implements ProxmoxPropertyCast
{
    public function parse(string $value): bool
    {
        return (bool) (int) $value;
    }

    public function emit(mixed $value): ?string
    {
        return $value ? '1' : '0';
    }
}
