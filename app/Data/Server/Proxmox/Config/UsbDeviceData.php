<?php

namespace App\Data\Server\Proxmox\Config;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

class UsbDeviceData extends Data
{
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
        public ?string $mapping,

        /**
         * Whether this is a USB3 device or port.
         * For modern guests, this flag is irrelevant (all devices are plugged into a xhci controller).
         */
        #[MapOutputName('is_usb3')]
        public bool $isUsb3,
    ) {}

    public static function fromRaw(string $id, string $rawValue): self
    {
        // Parse the USB device properties string
        $parts = explode(',', $rawValue);
        $firstPart = array_shift($parts);

        // Initialize default values
        $host = null;
        $mapping = null;
        $isUsb3 = false;

        // Extract the device ID from the key (e.g., "usb0", "usb1", etc.)
        $deviceId = 0;
        if (preg_match('/^usb(\d+)$/', $id, $matches)) {
            $deviceId = (int) $matches[1];
        }

        // Check if the first part has key=value format
        if (strpos($firstPart, '=') !== false) {
            [$key, $value] = explode('=', $firstPart, 2);
            if ($key === 'host') {
                $host = $value;
            } elseif ($key === 'mapping') {
                $mapping = $value;
            }
        } else {
            // If no '=' in the first part, it's the host parameter (default key)
            $host = $firstPart;
        }

        // Create a parameters array for easier handling
        $parameters = [];
        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }

            $keyValue = explode('=', $part, 2);
            if (count($keyValue) !== 2) {
                continue;
            }

            $paramKey = $keyValue[0];
            $value = $keyValue[1];
            $parameters[$paramKey] = $value;
        }

        // Check for mapping if not already set
        if ($mapping === null && isset($parameters['mapping'])) {
            $mapping = $parameters['mapping'];
        }

        // Check for host if not already set
        if ($host === null && isset($parameters['host'])) {
            $host = $parameters['host'];
        }

        // Check for USB3 flag
        if (isset($parameters['usb3'])) {
            $isUsb3 = filter_var($parameters['usb3'], FILTER_VALIDATE_BOOLEAN);
        }

        return new self(
            id: $deviceId,
            host: $host,
            mapping: $mapping,
            isUsb3: $isUsb3,
        );
    }
}
