<?php

namespace App\Data\Server\Proxmox\Config;

use App\Extensions\Spatie\Data\Proxmox\MapsProxmoxProperties;
use App\Extensions\Spatie\Data\Proxmox\PropertyList;
use App\Extensions\Spatie\Data\Proxmox\ProxmoxProperty;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

class TpmStateDiskData extends Data
{
    use MapsProxmoxProperties;

    public function __construct(
        /**
         * @var $volume
         *
         * The drive's backing volume. This is the storage location for the TPM state.
         * Example: "local-lvm:vm-100-disk-2"
         * This is the default (positional) key of the property list.
         */
        public string $volume,

        /**
         * @var $version
         */
        #[ProxmoxProperty('version')]
        public string $version,

        /**
         * @var $size
         *
         * Disk size. This is purely informational for TPM state disks and has no functional effect.
         */
        #[ProxmoxProperty('size')]
        public int $size,

        /**
         * @var array<string, string> $extraProperties
         *
         * Sub-keys present on the tpmstate string that we don't explicitly model.
         * Preserved verbatim so re-emitting never drops a field PVE set.
         */
        public array $extraProperties = [],
    ) {}

    /**
     * Creates a TpmStateDiskData instance from a raw Proxmox tpmstate0 config string.
     * Example raw string: "local-lvm:vm-100-disk-2,size=4M,version=v2.0"
     * Or just: "local-lvm:vm-100-disk-2"
     *
     * @param  string  $raw  The raw configuration string from Proxmox API.
     */
    public static function fromRaw(string $raw): self
    {
        [$head, $pairs] = PropertyList::explode($raw);

        // The head is always the backing volume — bare, or keyed as file=/volume=.
        $volume = $head;
        if (Str::contains($head, '=')) {
            [$key, $value] = explode('=', $head, 2);
            if (in_array(trim($key), ['file', 'volume'], true)) {
                $volume = trim($value);
            }
        }

        [$mapped, $extraProperties] = self::mapProxmoxProperties($pairs);

        return new self(
            volume: $volume,
            version: $mapped['version'] ?? '',
            size: $mapped['size'] ?? 0,
            extraProperties: $extraProperties,
        );
    }

    /**
     * Converts the Data Object back to the Proxmox API string format.
     */
    public function toProxmoxString(): string
    {
        // Emit the volume explicitly as file= for clarity, then the modeled
        // keys, then any sub-keys we don't model.
        $pairs = $this->toProxmoxProperties() + $this->extraProperties;

        return PropertyList::implode('file='.$this->volume, $pairs);
    }
}
