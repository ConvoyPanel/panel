<?php

namespace App\Data\Server\Proxmox\Config;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

class ServerHotplugFeaturesData extends Data
{
    public function __construct(
        public bool $isCpuEnabled,
        public bool $isMemoryEnabled,
        public bool $isNetworkEnabled,
        public bool $isDiskEnabled,
        public bool $isUsbEnabled,
        public bool $isCloudinitEnabled,
    ) {}

    public static function fromRaw(array $raw): self
    {
        if (Arr::get($raw, 'hotplug') === '0') {
            return new self(
                isCpuEnabled: false,
                isMemoryEnabled: false,
                isNetworkEnabled: false,
                isDiskEnabled: false,
                isUsbEnabled: false,
                isCloudinitEnabled: false,
            );
        }

        if (!Arr::exists($raw, 'hotplug') || Arr::get($raw, 'hotplug') === '1') {
            return new self(
                isCpuEnabled: false,
                isMemoryEnabled: false,
                isNetworkEnabled: true,
                isDiskEnabled: true,
                isUsbEnabled: true,
                isCloudinitEnabled: false,
            );
        }

        $isEnabled = fn (string $feature) => Str::contains($raw['hotplug'], $feature);

        return new self(
            isCpuEnabled: $isEnabled('cpu'),
            isMemoryEnabled: $isEnabled('memory'),
            isNetworkEnabled: $isEnabled('network'),
            isDiskEnabled: $isEnabled('disk'),
            isUsbEnabled: $isEnabled('usb'),
            isCloudinitEnabled: $isEnabled('cloudinit'),
        );
    }
}
