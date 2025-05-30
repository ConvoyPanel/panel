<?php

namespace App\Data\Server\Proxmox\Config;

use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

class CpuConfigData extends Data
{
    public function __construct(

        /**
         * @var $emulatedType
         *
         * Specifies the type of CPU that will be emulated for the VM. `host` passes through host CPU features (good for performance, limits migration). `kvm64` is a generic, compatible CPU type. Other specific models can be chosen.
         */
        public string $emulatedType,

        /**
         * @var $coreCount
         *
         * The number of CPU cores assigned to each virtual CPU socket in the VM. Total vCPUs = sockets * cores
         */
        public int $coreCount,

        public int $socketCount,

        /**
         * @var $usageLimit
         *
         * Limits how much of the host's CPU power the VM can use.
         */
        public float $usageLimit,

        /**
         * @var $weight
         *
         * A weight determining the VM's share of CPU time if competing for resources. Used by the kernel fair scheduler. Larger number = more CPU time.
         */
        public int $weight,

        public bool $freezeAtStartup,

        public bool $isNumaEnabled,

        // TODO: parsing of NUMA topology

        public int $hotpluggedVCpuCount,

        /**
         * @var $affinity
         *
         * If the main computer (the Proxmox host) has multiple processor cores (like brains),
         * this setting lets you choose which specific cores the VM is allowed to use.
         * For example, you could say "only use cores 0, 5, and 8 through 11."
         */
        public ?string $affinity,
    ) {}

    public static function fromRaw(array $raw): self
    {
        $get = fn (string $key, $default = null) => Arr::get($raw, $key, $default);

        return new self(
            emulatedType: $get('cpu', 'kvm64'),
            coreCount: $get('cores', 1),
            socketCount: $get('sockets', 1),
            usageLimit: $get('cpulimit', 0),
            weight: $get('cpuunits', 100),
            freezeAtStartup: $get('freeze', false),
            isNumaEnabled: $get('numa', false),
            hotpluggedVCpuCount: $get('vcpus', 0),
            affinity: $get('affinity'),
        );
    }
}
