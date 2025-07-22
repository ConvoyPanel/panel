<?php

namespace App\Data\Server\Proxmox\Config;

use App\Enums\Server\Disk\DiskAioMode;
use App\Enums\Server\Disk\DiskCacheMode;
use App\Enums\Server\Disk\DiskDiscardMode;
use App\Enums\Server\Disk\DiskFormat;
use App\Enums\Server\Disk\DiskMediaType;
use App\Enums\Server\Disk\DiskReadErrorAction;
use App\Enums\Server\Disk\DiskTranslationMode;
use App\Enums\Server\Disk\DiskWriteErrorAction;
use App\Enums\Server\DiskInterface;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

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
        public ?int $bps,
        #[MapOutputName('bps_max')]
        public ?int $bpsMax,
        #[MapOutputName('bps_read')]
        public ?int $bpsRead,
        #[MapOutputName('bps_read_max')]
        public ?int $bpsReadMax,
        #[MapOutputName('bps_write')]
        public ?int $bpsWrite,
        #[MapOutputName('bps_write_max')]
        public ?int $bpsWriteMax,
        public ?int $iops,
        #[MapOutputName('iops_max')]
        public ?int $iopsMax,
        #[MapOutputName('iops_read')]
        public ?int $iopsRead,
        #[MapOutputName('iops_read_max')]
        public ?int $iopsReadMax,
        #[MapOutputName('iops_write')]
        public ?int $iopsWrite,
        #[MapOutputName('iops_write_max')]
        public ?int $iopsWriteMax,
        public ?bool $isSnapshot,
        public bool $isShared,
        #[MapOutputName('detect_zeroes')]
        public bool $detectZeroes,
        #[MapOutputName('read_error_action')]
        public ?DiskReadErrorAction $readErrorAction,
        #[MapOutputName('write_error_action')]
        public ?DiskWriteErrorAction $writeErrorAction,
        #[MapOutputName('translation_mode')]
        public ?DiskTranslationMode $translationMode,
        public ?string $wwn,
        #[MapOutputName('bps_max_length')]
        public ?int $bpsMaxLength,
        #[MapOutputName('bps_read_max_length')]
        public ?int $bpsReadMaxLength,
        #[MapOutputName('bps_write_max_length')]
        public ?int $bpsWriteMaxLength,
        public ?int $cylinders,
        public ?int $heads,
        #[MapOutputName('iops_max_length')]
        public ?int $iopsMaxLength,
        #[MapOutputName('iops_read_max_length')]
        public ?int $iopsReadMaxLength,
        #[MapOutputName('iops_write_max_length')]
        public ?int $iopsWriteMaxLength,
        public ?string $model,
        public ?string $product,
        public ?int $queues,
        #[MapOutputName('is_scsi_block')]
        public bool $isScsiBlock,
        public ?int $sectors,
        public ?string $serial,
        public ?string $vendor,
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
        $bps = null;
        $bpsMax = null;
        $bpsRead = null;
        $bpsReadMax = null;
        $bpsWrite = null;
        $bpsWriteMax = null;
        $iops = null;
        $iopsMax = null;
        $iopsRead = null;
        $iopsReadMax = null;
        $iopsWrite = null;
        $iopsWriteMax = null;
        $isSnapshot = null;
        $isShared = false;
        $detectZeroes = false;
        $readErrorAction = null;
        $writeErrorAction = null;
        $translationMode = null;
        $wwn = null;
        $bpsMaxLength = null;
        $bpsReadMaxLength = null;
        $bpsWriteMaxLength = null;
        $cylinders = null;
        $heads = null;
        $iopsMaxLength = null;
        $iopsReadMaxLength = null;
        $iopsWriteMaxLength = null;
        $model = null;
        $product = null;
        $queues = null;
        $isScsiBlock = false;
        $sectors = null;
        $serial = null;
        $vendor = null;

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
            if (blank($part)) {
                continue;
            }

            $keyValue = explode('=', $part, 2);
            $paramKey = $keyValue[0];
            $value = $keyValue[1] ?? true; // If no value is provided, assume true for boolean flags
            $parameters[$paramKey] = $value;
        }

        // Process parameters with match expressions where possible
        if (filled(data_get($parameters, 'media'))) {
            $diskMediaType = match (data_get($parameters, 'media')) {
                'cdrom' => DiskMediaType::CDROM,
                default => DiskMediaType::DISK,
            };
        }

        if (filled(data_get($parameters, 'size'))) {
            if (preg_match('/^(\d+)([KMGT])?$/', data_get($parameters, 'size'), $matches)) {
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

        if (filled(data_get($parameters, 'format'))) {
            $format = DiskFormat::tryFrom(data_get($parameters, 'format')) ?? DiskFormat::RAW;
        }

        if (filled(data_get($parameters, 'cache'))) {
            $cacheMode = DiskCacheMode::tryFrom(data_get($parameters, 'cache'));
        }

        if (filled(data_get($parameters, 'aio'))) {
            $aioMode = DiskAioMode::tryFrom(data_get($parameters, 'aio'));
        }

        if (filled(data_get($parameters, 'discard'))) {
            $discardMode = DiskDiscardMode::tryFrom(data_get($parameters, 'discard'));
        }

        if (filled(data_get($parameters, 'rerror'))) {
            $readErrorAction = DiskReadErrorAction::tryFrom(data_get($parameters, 'rerror'));
        }

        if (filled(data_get($parameters, 'werror'))) {
            $writeErrorAction = DiskWriteErrorAction::tryFrom(data_get($parameters, 'werror'));
        }

        if (filled(data_get($parameters, 'trans'))) {
            $translationMode = DiskTranslationMode::tryFrom(data_get($parameters, 'trans'));
        }

        if (filled(data_get($parameters, 'mbps'))) {
            $bps = (int) (data_get($parameters, 'mbps') * 1024 * 1024);
        } elseif (filled(data_get($parameters, 'bps'))) {
            $bps = (int) data_get($parameters, 'bps');
        }

        if (filled(data_get($parameters, 'mbps_rd'))) {
            $bpsRead = (int) (data_get($parameters, 'mbps_rd') * 1024 * 1024);
        } elseif (filled(data_get($parameters, 'bps_rd'))) {
            $bpsRead = (int) data_get($parameters, 'bps_rd');
        }

        if (filled(data_get($parameters, 'mbps_wr'))) {
            $bpsWrite = (int) (data_get($parameters, 'mbps_wr') * 1024 * 1024);
        } elseif (filled(data_get($parameters, 'bps_wr'))) {
            $bpsWrite = (int) data_get($parameters, 'bps_wr');
        }

        // Integer parameters
        if (filled(data_get($parameters, 'mbps_max'))) {
            $bpsMax = (int) (data_get($parameters, 'mbps_max') * 1024 * 1024);
        }
        if (filled(data_get($parameters, 'mbps_rd_max'))) {
            $bpsReadMax = (int) (data_get($parameters, 'mbps_rd_max') * 1024 * 1024);
        }
        if (filled(data_get($parameters, 'mbps_wr_max'))) {
            $bpsWriteMax = (int) (data_get($parameters, 'mbps_wr_max') * 1024 * 1024);
        }

        $iops = (int) data_get($parameters, 'iops');
        $iopsMax = (int) data_get($parameters, 'iops_max');
        $iopsRead = (int) data_get($parameters, 'iops_rd');
        $iopsReadMax = (int) data_get($parameters, 'iops_rd_max');
        $iopsWrite = (int) data_get($parameters, 'iops_wr');
        $iopsWriteMax = (int) data_get($parameters, 'iops_wr_max');
        $bpsMaxLength = (int) data_get($parameters, 'bps_max_length');

        $bpsReadMaxLength = data_get($parameters, 'bps_rd_max_length', data_get($parameters, 'bps_rd_length'));
        if (filled($bpsReadMaxLength)) {
            $bpsReadMaxLength = (int) $bpsReadMaxLength;
        }

        $bpsWriteMaxLength = data_get($parameters, 'bps_wr_max_length', data_get($parameters, 'bps_wr_length'));
        if (filled($bpsWriteMaxLength)) {
            $bpsWriteMaxLength = (int) $bpsWriteMaxLength;
        }

        $cylinders = (int) data_get($parameters, 'cyls');
        $heads = (int) data_get($parameters, 'heads');
        $iopsMaxLength = (int) data_get($parameters, 'iops_max_length');

        $iopsReadMaxLength = data_get($parameters, 'iops_rd_max_length', data_get($parameters, 'iops_rd_length'));
        if (filled($iopsReadMaxLength)) {
            $iopsReadMaxLength = (int) $iopsReadMaxLength;
        }

        $iopsWriteMaxLength = data_get($parameters, 'iops_wr_max_length', data_get($parameters, 'iops_wr_length'));
        if (filled($iopsWriteMaxLength)) {
            $iopsWriteMaxLength = (int) $iopsWriteMaxLength;
        }

        $queues = (int) data_get($parameters, 'queues');
        $sectors = (int) data_get($parameters, 'secs');

        // Boolean parameters
        $isEmulatingSSD = filter_var(data_get($parameters, 'ssd', false), FILTER_VALIDATE_BOOLEAN);
        $isIncludedInBackup = filter_var(data_get($parameters, 'backup', true), FILTER_VALIDATE_BOOLEAN);
        $isReplicated = filter_var(data_get($parameters, 'replicate', true), FILTER_VALIDATE_BOOLEAN);
        $isReadonly = filter_var(data_get($parameters, 'ro', false), FILTER_VALIDATE_BOOLEAN);
        $isIOThreadEnabled = filter_var(data_get($parameters, 'iothread', false), FILTER_VALIDATE_BOOLEAN);
        $isSnapshot = filter_var(data_get($parameters, 'snapshot', false), FILTER_VALIDATE_BOOLEAN);
        $isShared = filter_var(data_get($parameters, 'shared', false), FILTER_VALIDATE_BOOLEAN);
        $detectZeroes = filter_var(data_get($parameters, 'detect_zeroes', false), FILTER_VALIDATE_BOOLEAN);
        $isScsiBlock = filter_var(data_get($parameters, 'scsiblock', false), FILTER_VALIDATE_BOOLEAN);

        // String parameters
        $wwn = data_get($parameters, 'wwn');
        $model = data_get($parameters, 'model');
        $product = data_get($parameters, 'product');
        $serial = data_get($parameters, 'serial');
        $vendor = data_get($parameters, 'vendor');

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
            bps: $bps,
            bpsMax: $bpsMax,
            bpsRead: $bpsRead,
            bpsReadMax: $bpsReadMax,
            bpsWrite: $bpsWrite,
            bpsWriteMax: $bpsWriteMax,
            iops: $iops,
            iopsMax: $iopsMax,
            iopsRead: $iopsRead,
            iopsReadMax: $iopsReadMax,
            iopsWrite: $iopsWrite,
            iopsWriteMax: $iopsWriteMax,
            isSnapshot: $isSnapshot,
            isShared: $isShared,
            detectZeroes: $detectZeroes,
            readErrorAction: $readErrorAction,
            writeErrorAction: $writeErrorAction,
            translationMode: $translationMode,
            wwn: $wwn,
            bpsMaxLength: $bpsMaxLength,
            bpsReadMaxLength: $bpsReadMaxLength,
            bpsWriteMaxLength: $bpsWriteMaxLength,
            cylinders: $cylinders,
            heads: $heads,
            iopsMaxLength: $iopsMaxLength,
            iopsReadMaxLength: $iopsReadMaxLength,
            iopsWriteMaxLength: $iopsWriteMaxLength,
            model: $model,
            product: $product,
            queues: $queues,
            isScsiBlock: $isScsiBlock,
            sectors: $sectors,
            serial: $serial,
            vendor: $vendor,
        );
    }
}
