<?php

namespace App\Extensions\Spatie\Data\Proxmox;

/**
 * Converts a single Proxmox property-list value between its raw string form
 * (as it appears in a PVE config line, e.g. the "1" in "firewall=1") and the
 * typed PHP value stored on a DTO property.
 *
 * This is a *second* serialization axis, separate from spatie/laravel-data's
 * name mapping (which is committed to the frontend/TS JSON contract). It is
 * driven by the {@see ProxmoxProperty} attribute, not by laravel-data.
 */
interface ProxmoxPropertyCast
{
    /**
     * Parse a raw PVE value string into the typed PHP value for the property.
     */
    public function parse(string $value): mixed;

    /**
     * Emit the typed PHP value back to a PVE value string, or null to omit the
     * key entirely from the emitted property list.
     */
    public function emit(mixed $value): ?string;
}
