<?php

namespace App\Extensions\Spatie\Data\Proxmox;

use Attribute;

/**
 * Marks a DTO constructor property as a Proxmox property-list "tail" key
 * (i.e. one of the `key=value` pairs that follow the positional head of a
 * config line such as `net0` or `scsi0`).
 *
 * The attribute declares the PVE key name and, when the value needs more than
 * trivial int/string/enum coercion, an explicit {@see ProxmoxPropertyCast}.
 * The {@see MapsProxmoxProperties} trait reads these attributes to parse and
 * re-emit the tail, so the DTO no longer needs hand-written `isset(...)`
 * ladders or `if ($x !== null)` append blocks.
 */
// Promoted constructor properties are reflected as both a parameter and a
// property; laravel-data introspects the property side, so allow both.
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class ProxmoxProperty
{
    /**
     * @param  string  $key  The PVE property-list key (e.g. "firewall", "tag", "rate").
     * @param  class-string<ProxmoxPropertyCast>|null  $cast  Explicit cast for
     *         values that are not a plain int, string, or backed enum. int,
     *         string, and backed-enum properties are handled automatically.
     */
    public function __construct(
        public string $key,
        public ?string $cast = null,
    ) {}
}
