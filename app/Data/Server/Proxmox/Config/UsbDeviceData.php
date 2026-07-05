<?php

namespace App\Data\Server\Proxmox\Config;

use App\Extensions\Spatie\Data\Proxmox\Casts\PveBooleanCast;
use App\Extensions\Spatie\Data\Proxmox\MapsProxmoxProperties;
use App\Extensions\Spatie\Data\Proxmox\PropertyList;
use App\Extensions\Spatie\Data\Proxmox\ProxmoxProperty;
use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

class UsbDeviceData extends Data
{
    use MapsProxmoxProperties;

    public function __construct(
        /**
         * The identifier of the USB device.
         */
        public int $id,

        /**
         * The host USB device or port or 'spice'.
         * Format can be:
         * - 'bus-port(.port)*' (decimal numbers)
         * - 'vendor_id:product_id' (hexadecimal numbers)
         * - 'spice'
         */
        public ?string $host,

        /**
         * The ID of a cluster wide mapping.
         */
        #[ProxmoxProperty('mapping')]
        public ?string $mapping,

        /**
         * Whether this is a USB3 device or port.
         * For modern guests, this flag is irrelevant (all devices are plugged into a xhci controller).
         */
        #[MapOutputName('is_usb3')]
        #[ProxmoxProperty('usb3', PveBooleanCast::class)]
        public bool $isUsb3,
    ) {}

    public static function fromRaw(string $id, string $rawValue): self
    {
        // Extract the device ordinal from the key (e.g., "usb0", "usb1", ...).
        $deviceId = 0;
        if (preg_match('/^usb(\d+)$/', $id, $matches)) {
            $deviceId = (int) $matches[1];
        }

        [$head, $pairs] = PropertyList::explode($rawValue);
        [$mapped] = self::mapProxmoxProperties($pairs);

        // The head is the default `host` key — bare or keyed as `host=`. A
        // cluster-mapped passthrough omits host entirely and leads with
        // `mapping=` instead, so the head can carry either field.
        $host = null;
        $mapping = $mapped['mapping'] ?? null;
        if (Str::contains($head, '=')) {
            [$key, $value] = explode('=', $head, 2);
            if (trim($key) === 'host') {
                $host = trim($value);
            } elseif (trim($key) === 'mapping') {
                $mapping = trim($value);
            }
        } else {
            $host = $head;
        }

        return new self(
            id: $deviceId,
            host: $host,
            mapping: $mapping,
            isUsb3: $mapped['isUsb3'] ?? false,
        );
    }
}
