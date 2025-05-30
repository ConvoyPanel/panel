<?php

namespace App\Data\Server\Proxmox\Config;

use App\Enums\Server\Disk\DiskAioMode;
use App\Enums\Server\Disk\DiskCacheMode;
use App\Enums\Server\Disk\DiskDiscardMode;
use App\Enums\Server\Disk\DiskFormat;
use App\Enums\Server\Disk\DiskMediaType;
use App\Enums\Server\DiskInterface;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapOutputName;

class DiskData extends Data
{
    public function __construct(
        public DiskInterface $interface,
        public string $volume,
        public DiskMediaType $diskMediaType,
        public int $size,
        public DiskFormat $format,
        public ?DiskCacheMode $cacheMode,
        public ?DiskAioMode $aioMode,
        public ?DiskDiscardMode $discardMode,
        #[MapOutputName('is_emulating_ssd')]
        public bool $isEmulatingSSD,
        public bool $isIncludedInBackup,
        public bool $isReplicated,
        public bool $isReadonly,
        #[MapOutputName('is_io_thread_enabled')]
        public bool $isIOThreadEnabled,
    ) {}

    /**
     * Get the full disk identifier (interface + slot number)
     */
    public function getFullIdentifier(): string
    {
        return $this->interface->value;
    }

    /**
     * Get the base interface type (ide, sata, scsi, virtio, etc.)
     */
    public function getBaseInterfaceType(): string
    {
        return $this->interface->getBaseType();
    }

    public static function fromRaw(string $key, string $rawValue): self
    {
        // Parse the disk properties string
        $parts = explode(',', $rawValue);
        $volume = array_shift($parts);

        // Extract 'file=' prefix if it exists
        if (str_starts_with($volume, 'file=')) {
            $volume = substr($volume, 5);
        }

        // Initialize default values
        $diskMediaType = DiskMediaType::DISK;
        $size = 0;
        $format = DiskFormat::RAW;
        $cacheMode = null;
        $aioMode = null;
        $discardMode = null;

        // Extract interface and ID from the key (e.g., "ide0", "sata1", "scsi2", etc.)
        $interface = DiskInterface::IDE0; // Default
        
        if (preg_match('/^(ide|sata|scsi|virtio|efidisk|tpmstate)(\d+)$/', $key, $matches)) {
            $interfaceType = $matches[1];
            $id = (int) $matches[2];

            // Convert to the enum value
            $interfaceName = strtoupper($interfaceType).$id;
            if (defined(DiskInterface::class.'::'.$interfaceName)) {
                $interface = constant(DiskInterface::class.'::'.$interfaceName);
            }
        }

        // Create a parameters array for easier handling
        $parameters = [];
        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }

            $keyValue = explode('=', $part, 2);
            $paramKey = $keyValue[0];
            $value = $keyValue[1] ?? true; // If no value is provided, assume true for boolean flags
            $parameters[$paramKey] = $value;
        }

        // Process parameters with match expressions where possible
        if (isset($parameters['media'])) {
            $diskMediaType = match ($parameters['media']) {
                'cdrom' => DiskMediaType::CDROM,
                default => DiskMediaType::DISK,
            };
        }

        if (isset($parameters['size'])) {
            if (preg_match('/^(\d+)([KMGT])?$/', $parameters['size'], $matches)) {
                $sizeValue = (int) $matches[1];
                $sizeUnit = $matches[2] ?? '';
                
                $size = match ($sizeUnit) {
                    'K' => $sizeValue * 1024,
                    'M' => $sizeValue * 1024 * 1024,
                    'G' => $sizeValue * 1024 * 1024 * 1024,
                    'T' => $sizeValue * 1024 * 1024 * 1024 * 1024,
                    default => $sizeValue,
                };
            }
        }

        if (isset($parameters['format']) && in_array($parameters['format'], ['raw', 'qcow', 'qed', 'qcow2', 'vmdk', 'cloop'])) {
            $format = DiskFormat::from($parameters['format']);
        }

        if (isset($parameters['cache']) && in_array($parameters['cache'], ['none', 'writethrough', 'writeback', 'unsafe', 'directsync'])) {
            $cacheMode = DiskCacheMode::from($parameters['cache']);
        }

        if (isset($parameters['aio']) && in_array($parameters['aio'], ['native', 'threads', 'io_uring'])) {
            $aioMode = DiskAioMode::from($parameters['aio']);
        }

        if (isset($parameters['discard']) && in_array($parameters['discard'], ['ignore', 'on'])) {
            $discardMode = DiskDiscardMode::from($parameters['discard']);
        }

        // Boolean parameters
        $isEmulatingSSD = filter_var($parameters['ssd'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $isIncludedInBackup = filter_var($parameters['backup'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $isReplicated = filter_var($parameters['replicate'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $isReadonly = filter_var($parameters['ro'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $isIOThreadEnabled = filter_var($parameters['iothread'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return new self(
            interface: $interface,
            volume: $volume,
            diskMediaType: $diskMediaType,
            size: $size,
            format: $format,
            cacheMode: $cacheMode,
            aioMode: $aioMode,
            discardMode: $discardMode,
            isEmulatingSSD: $isEmulatingSSD,
            isIncludedInBackup: $isIncludedInBackup,
            isReplicated: $isReplicated,
            isReadonly: $isReadonly,
            isIOThreadEnabled: $isIOThreadEnabled,
        );
    }
}
