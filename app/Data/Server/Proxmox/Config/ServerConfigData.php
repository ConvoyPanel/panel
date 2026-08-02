<?php

namespace App\Data\Server\Proxmox\Config;

use App\Enums\Server\BiosType;
use App\Enums\Server\HugePagesSetting;
use App\Enums\Server\OperatingSystemType;
use App\Enums\Server\ProxmoxLock;
use App\Support\ByteUnit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use IPLib\Address\AddressInterface;
use IPLib\Factory;
use Spatie\LaravelData\Data;

use function collect;
use function preg_match;

class ServerConfigData extends Data
{
    public function __construct(
        public ?string $description,

        public bool $isTemplate,

        public ?string $tags,

        /**
         * @var $isAcpiEnabled
         *
         * A way for the operating system inside your VM (like Windows or Linux)
         * to talk to the virtual hardware about power management, like shutting down or going to sleep.
         * Setting: You can turn this on or off (it's usually on by default).
         * It's generally good to keep it enabled unless you have a specific reason not to.
         */
        public bool $isAcpiEnabled,

        /**
         * @var $amdSevFeatures
         *
         * This is a security feature for AMD processors called Secure Encrypted
         * Virtualization (SEV). If your Proxmox host's CPU supports it, this can help
         * protect the VM's memory by encrypting it.
         */
        public ?string $amdSevFeatures,

        /**
         * @var $architecture
         *
         * Architecture: Specifies the processor architecture for the VM, like `x86_64`
         * (most common for PCs and servers) or `aarch64` (common for ARM-based devices).
         */
        public ?string $architecture,

        /**
         * @var $kvmArguments
         *
         * This allows you to pass extra, very specific command-line arguments
         * directly to KVM (the underlying virtualization technology).
         */
        public ?string $kvmArguments,

        /**
         * @var $autoStartAfterCrash
         *
         * This setting is meant to automatically restart the VM if it crashes.
         */
        public bool $autoStartAfterCrash,

        /**
         * @var $memoryBalloonSize
         *
         * Relates to dynamic memory management. If enabled, the VM can "balloon"
         * its memory usage up or down as needed, allowing the host to reclaim unused memory.
         */
        public ?int $memoryBalloonSize,

        /**
         * @var $memorySharesForAutoBallooning
         *
         * Amount of memory shares for auto-ballooning. Larger number = more memory priority.
         */
        public int $memorySharesForAutoBallooning,

        public BiosType $biosType,

        /**
         * @var Collection<int, DiskData> $bootOrder
         *
         * Specifies the order in which the VM tries to boot from different devices.
         */
        public Collection $bootOrder,

        /**
         * @var $cdromImage
         *
         * Used to mount an ISO file. An alias or shortcut for configuring a virtual CD/DVD drive (specifically, ide2).
         */
        public ?string $cdromImage,

        public CloudinitConfigData $cloudinit,

        public CpuConfigData $cpu,

        public int $memory,

        /**
         * @var $hookScriptVolumeId
         *
         * Script on the Proxmox host executed during VM lifetime steps (e.g., pre-start, post-stop).
         */
        public ?string $hookScriptVolumeId,

        /**
         * @var $hotplugFeatures
         *
         * Selectively enable hotplug features (network, disk, cpu, memory, usb, cloudinit).
         */
        public ServerHotplugFeaturesData $hotplugFeatures,

        public ?HugePagesSetting $hugePagesSetting,

        public bool $keepHugePagesOnShutdown,

        public ?string $vncKeyboardLayout,

        public bool $isKvmHardwareVirtualizationEnabled,

        public ?bool $isRtcUsingLocalTime,

        public ?CarbonImmutable $rtcStartDate,

        public ?ProxmoxLock $lockStatus,

        public ?string $qemuConfig,
        /**
         * @var $migrationMaxDowntime
         *
         * Maximum tolerated downtime (seconds) for live migrations.
         */
        public ?float $migrationMaxDowntime,

        /**
         * @var $migrationMaxSpeed
         *
         * Maximum speed (B/s) for migrations.
         */
        public ?int $migrationMaxSpeed,

        /**
         * @var $name
         *
         * Set a name for the VM. Only used on the PVE web interface.
         */
        public ?string $name,
        /**
         * @var Collection<int, AddressInterface> $nameservers
         */
        public Collection $nameservers,

        /**
         * @var Collection<int, NetworkDeviceData> $networkDevices
         */
        public Collection $networkDevices,

        public bool $startOnHostBoot,

        public OperatingSystemType $operatingSystemType,

        /**
         * @var $isProtected
         *
         * If enabled, disables remove VM and remove disk operations.
         */
        public bool $isProtected,

        /**
         * @var $isRebootAllowed
         *
         * Allow reboot. If false, VM exits on reboot.
         */
        public bool $isRebootAllowed,

        /**
         * @var $rngDevice
         *
         * Configure a VirtIO-based Random Number Generator
         */
        public ?string $rngDevice,

        /**
         * @var $smbiosConfig
         *
         * Specify SMBIOS type 1 fields (system information).
         */
        public string $smbiosConfig,

        public ?string $startupShutdownBehavior,

        public bool $isUsbTabletEnabled,

        public bool $isTimeDriftFixEnabled,

        public ?TpmStateDiskData $tpmStateDisk,

        /**
         * @var Collection<int, string> $unusedDisks
         *
         * Reference to unused volumes. This is used internally, and should not be modified manually.
         *
         * TODO: implement unused disk configuration parsing
         */
        public Collection $unusedDisks,

        /**
         * @var Collection<int, UsbDeviceData> $usbDevices
         *
         * TODO: Implement USB device configuration.
         */
        public Collection $usbDevices,

        /**
         * @var Collection<int, DiskData> $disks
         */
        public Collection $disks,

        /**
         * @var Collection<int, string> $virtioFileSystems
         *
         * TODO: Implement VirtioFS configuration.
         */
        public Collection $virtioFileSystems,

        /**
         * @var Collection<int, string> $parallelDevices
         */
        public Collection $parallelDevices,

        /**
         * The VM's serial devices, as their configured backing (`socket`, or a
         * passed-through host device). Shaped like `$parallelDevices`.
         *
         * Empty means the terminal console has nothing to attach to: PVE's
         * `termproxy` opens a serial terminal only against one of these.
         *
         * @var Collection<int, string> $serialDevices
         */
        public Collection $serialDevices,

        /**
         * SHA1 digest of the config at fetch time. Echo it back on an update so
         * PVE rejects the write if the config changed underneath us (optimistic
         * concurrency).
         */
        public ?string $digest = null,

        // NOTE: not all properties are added
    ) {}

    public static function fromRaw(array $raw): self
    {
        $get = fn (string $key, $default = null) => Arr::get($raw, $key, $default);
        $exists = fn (string $key) => Arr::exists($raw, $key);

        // Process disks first since we need them for boot order
        $disks = collect($raw)
            ->filter(fn ($value, $key) => preg_match('/^(virtio|sata|scsi|ide)\d+$/', $key))
            ->map(fn ($value, $key) => DiskData::fromRaw($key, $value));

        // Process boot order by matching disk identifiers with parsed disks
        $bootDiskIdentifiers = [];

        // Parse boot order, which might be in 'legacy=' format or 'order=' format
        if (isset($raw['boot'])) {
            $bootConfig = $raw['boot'];
            if (is_string($bootConfig)) {
                // Handle legacy format (comma-separated list or just a single value)
                if (str_contains($bootConfig, 'order=')) {
                    // Extract from order=disk1;disk2 format
                    preg_match('/order=([^;]+)/', $bootConfig, $matches);
                    if (isset($matches[1])) {
                        $bootDiskIdentifiers = explode(';', $matches[1]);
                    }
                } elseif (str_contains($bootConfig, 'legacy=')) {
                    // Extract from legacy=c format (legacy boot order)
                    // This is not disk-based, but we could map c->ide0, d->ide1, etc if needed
                    // For now, we'll just leave it empty as we focus on disk identifiers
                } else {
                    // If it's just a single value like 'order=ide0'
                    $bootDiskIdentifiers = [$bootConfig];
                }
            } elseif (is_array($bootConfig) && isset($bootConfig['order'])) {
                // Handle array format with 'order' key
                $bootDiskIdentifiers = is_array($bootConfig['order'])
                    ? $bootConfig['order']
                    : explode(';', $bootConfig['order']);
            }
        }

        // Map boot order identifiers to actual disk objects
        $bootOrder = collect($bootDiskIdentifiers)
            ->map(function ($diskId) use ($disks) {
                return $disks->first(function ($disk) use ($diskId) {
                    return $disk->getFullIdentifier() === $diskId;
                });
            })
            ->filter(); // Remove any null values (disks that weren't found)

        return new self(
            description                       : $get('description'),
            isTemplate                        : $get('template', false),
            tags                              : $get('tags'),
            isAcpiEnabled                     : $get('acpi', true),
            amdSevFeatures                    : $get('amd-sev'),
            architecture                      : $get('arch'),
            kvmArguments                      : $get('args'),
            autoStartAfterCrash               : $get('autostart', false),
            memoryBalloonSize                 : $exists(
                'balloon',
            ) ? ByteUnit::Mebibytes->toBytes((int) $raw['balloon']) : null,
            memorySharesForAutoBallooning     : $get('shares', 1000),
            biosType                          : BiosType::from($get('bios', 'seabios')),
            bootOrder                         : $bootOrder,
            cdromImage                        : $get('cdrom'),
            cloudinit                         : CloudinitConfigData::fromRaw($raw),
            cpu                               : CpuConfigData::fromRaw($raw),
            memory                            : ByteUnit::Mebibytes->toBytes((float) $get('memory')),
            hookScriptVolumeId                : $get('hookscript'),
            hotplugFeatures                   : ServerHotplugFeaturesData::fromRaw($raw),
            hugePagesSetting                  : HugePagesSetting::tryFrom(
                $get('hugepages', ''),
            ),
            keepHugePagesOnShutdown           : $get('keephugepages', false),
            vncKeyboardLayout                 : $get('keyboard'),
            isKvmHardwareVirtualizationEnabled: $get('kvm', true),
            isRtcUsingLocalTime               : $get('localtime'),
            rtcStartDate                      : $exists('startdate') ? CarbonImmutable::parse(
                $get('startdate'),
            ) : null,
            lockStatus                        : ProxmoxLock::tryFrom(
                $get('lock', ''),
            ),
            qemuConfig                        : $get('machine'),
            migrationMaxDowntime              : $get('migrate_downtime', 0.1),
            migrationMaxSpeed                 : ! $exists('migrate_speed') || $get('migrate_speed', 0) === 0
                ? null
                : ByteUnit::Mebibytes->toBytes((float) $get('migrate_speed')), // Convert from MiB/s to B/s
            name                              : $get('name'),
            nameservers                       : collect($get('nameserver', []))
                ->map(fn (string $ns) => Factory::parseAddressString($ns)),
            networkDevices                    : NetworkDeviceData::fromRaw($raw),
            startOnHostBoot                   : $get('onboot', false),
            operatingSystemType               : OperatingSystemType::fromRaw($get('ostype', 'other')),
            isProtected                       : $get('protection', false),
            isRebootAllowed                   : $get('reboot', true),
            rngDevice                         : $get('rng0'),
            smbiosConfig                      : $get('smbios1'),
            startupShutdownBehavior           : $get('startup'),
            isUsbTabletEnabled                : $get('tablet', true),
            isTimeDriftFixEnabled             : $get('tdf', false),
            tpmStateDisk                      : $exists('tpmstate0') ? TpmStateDiskData::fromRaw(
                $get('tpmstate0'),
            ) : null,
            unusedDisks                       : collect($raw)
                ->filter(fn ($value, $key) => preg_match('/^unused\d+$/', $key))
                ->values(),
            usbDevices                        : collect($raw)
                ->filter(fn ($value, $key) => preg_match('/^usb\d+$/', $key))
                ->map(fn ($value, $key) => UsbDeviceData::fromRaw($key, $value)),
            disks                             : $disks,
            virtioFileSystems                 : collect($raw)
                ->filter(fn ($value, $key) => preg_match('/^virtiofs\d+$/', $key))
                ->values(),
            parallelDevices                   : collect($raw)
                ->filter(fn ($value, $key) => preg_match('/^parallel\d+$/', $key))
                ->values(),
            // PVE numbers these `serial0`..`serial3` and never emits a bare
            // `serial` key, so reading one left this permanently empty.
            serialDevices                     : collect($raw)
                ->filter(fn ($value, $key) => preg_match('/^serial\d+$/', $key))
                ->values(),
            digest                            : $get('digest'),
        );
    }
}
