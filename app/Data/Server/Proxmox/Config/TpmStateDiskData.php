<?php

namespace App\Data\Server\Proxmox\Config;

use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

class TpmStateDiskData extends Data
{
    public function __construct(
        /**
         * @var $volume
         *
         * The drive's backing volume. This is the storage location for the TPM state.
         * Example: "local-lvm:vm-100-disk-2"
         * This is the default key if only a volume is provided.
         */
        public string $volume,

        /**
         * @var $version
         */
        public string $version,

        /**
         * @var $size
         *
         * Disk size. This is purely informational for TPM state disks and has no functional effect.
         */
        public int $size,

    ) {}

    /**
     * Creates a TpmStateDiskData instance from a raw Proxmox tpmstate0 config string.
     * Example raw string: "local-lvm:vm-100-disk-2,size=4M,version=v2.0"
     * Or just: "local-lvm:vm-100-disk-2"
     *
     * @param string $raw The raw configuration string from Proxmox API.
     * @return TpmStateDiskData Returns an instance of TpmStateDiskData or null if parsing fails.
     */
    public static function fromRaw(string $raw): self
    {
        $parts = explode(',', trim($raw));
        $parsedValues = [];

        // The first part is always the volume, it might be the only part.
        // It might also be explicitly keyed like "file=<volume>" or "volume=<volume>"
        $firstPart = array_shift($parts);
        if (Str::contains($firstPart, '=')) {
            [$key, $value] = explode('=', $firstPart, 2);
            $key = trim($key);
            if ($key === 'file' || $key === 'volume') {
                $parsedValues['volume'] = trim($value);
            } else {
                // If the first part is a key-value but not 'file' or 'volume',
                // it's likely an implicit volume followed by other options.
                // This case is less common for tpmstate0 if it's *just* the volume.
                // For safety, let's assume if it's not explicitly file/volume, it's the volume value itself.
                // However, Proxmox usually makes the volume the default key.
                // Let's re-add it to parts if it's an unexpected key-value
                array_unshift($parts, $firstPart);
                // And try to parse the volume from the first part if it doesn't contain '='
                // This logic path is a bit complex, simpler to assume volume is first or keyed.
                // For tpmstate0, the volume is the default key.
                // So if $firstPart doesn't have '=', it's the volume.
                // If it has '=', and key is 'file' or 'volume', it's handled.
                // If it has '=' and key is something else, it's an error or complex case not handled here.
                // The Proxmox schema implies the volume can be the "default_key"
                // meaning it can appear without "file=" or "volume=".
                if (!isset($parsedValues['volume'])) {
                    // If we are here, it means the first part was not "file=..." or "volume=..."
                    // It could be just the volume ID, or another parameter if the volume was omitted (invalid for tpmstate)
                    // Let's assume if it doesn't contain '=', it's the volume.
                    if(!Str::contains($firstPart, '=')) {
                        $parsedValues['volume'] = trim($firstPart);
                    } else {
                        // First part is key=value but not file/volume. This is unexpected for the default key.
                        // Re-add to parts to be parsed as a regular option.
                        // This implies the volume itself might be missing if it wasn't the very first unkeyed value.
                        array_unshift($parts, $firstPart);
                    }
                }
            }
        } else {
            // First part does not contain '=', so it's the volume.
            $parsedValues['volume'] = trim($firstPart);
        }


        // Parse remaining key=value pairs
        foreach ($parts as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) === 2) {
                $parsedValues[trim($kv[0])] = trim($kv[1]);
            }
        }

        return new self(
            volume: $parsedValues['volume'],
            version: $parsedValues['version'],
            size: $parsedValues['size'] ?? null // Size is optional
        );
    }

    /**
     * Converts the Data Object back to the Proxmox API string format.
     */
    public function toProxmoxString(): string
    {
        $parts = [];
        // The volume is the primary part, often the default key
        $parts[] = 'file=' . $this->volume; // Be explicit with file= for clarity

        if ($this->size !== null) {
            $parts[] = 'size=' . $this->size;
        }
        // Only add version if it's not the default, or always add it for explicitness.
        // Proxmox defaults to v1.2 if 'version' is omitted.
        if ($this->version !== null) { // && $this->version !== TpmVersion::V1_2 (optional optimization)
            $parts[] = 'version=' . $this->version;
        }

        return implode(',', $parts);
    }
}
