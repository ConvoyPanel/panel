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
use App\Extensions\Spatie\Data\Proxmox\Casts\PveBooleanCast;
use App\Extensions\Spatie\Data\Proxmox\MapsProxmoxProperties;
use App\Extensions\Spatie\Data\Proxmox\PropertyList;
use App\Extensions\Spatie\Data\Proxmox\ProxmoxProperty;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

class DiskData extends Data
{
    use MapsProxmoxProperties;

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
        #[ProxmoxProperty('ssd', PveBooleanCast::class)]
        public bool $isEmulatingSSD,
        #[ProxmoxProperty('backup', PveBooleanCast::class)]
        public bool $isIncludedInBackup,
        #[ProxmoxProperty('replicate', PveBooleanCast::class)]
        public bool $isReplicated,
        #[ProxmoxProperty('ro', PveBooleanCast::class)]
        public bool $isReadonly,
        #[MapOutputName('is_io_thread_enabled')]
        #[ProxmoxProperty('iothread', PveBooleanCast::class)]
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
        #[ProxmoxProperty('snapshot', PveBooleanCast::class)]
        public ?bool $isSnapshot,
        #[ProxmoxProperty('shared', PveBooleanCast::class)]
        public bool $isShared,
        #[MapOutputName('detect_zeroes')]
        #[ProxmoxProperty('detect_zeroes', PveBooleanCast::class)]
        public bool $detectZeroes,
        #[MapOutputName('read_error_action')]
        public ?DiskReadErrorAction $readErrorAction,
        #[MapOutputName('write_error_action')]
        public ?DiskWriteErrorAction $writeErrorAction,
        #[MapOutputName('translation_mode')]
        public ?DiskTranslationMode $translationMode,
        #[ProxmoxProperty('wwn')]
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
        #[ProxmoxProperty('model')]
        public ?string $model,
        #[ProxmoxProperty('product')]
        public ?string $product,
        public ?int $queues,
        #[MapOutputName('is_scsi_block')]
        #[ProxmoxProperty('scsiblock', PveBooleanCast::class)]
        public bool $isScsiBlock,
        public ?int $sectors,
        #[ProxmoxProperty('serial')]
        public ?string $serial,
        #[ProxmoxProperty('vendor')]
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
        [$head, $pairs] = PropertyList::explode($rawValue);

        // The head is the backing volume, sometimes written explicitly as `file=<volume>`.
        $volume = str_starts_with($head, 'file=') ? substr($head, 5) : $head;

        // The interface (ide0, scsi1, ...) comes from the config key, not the value.
        $interface = DiskInterface::IDE0;
        if (preg_match('/^(ide|sata|scsi|virtio|efidisk|tpmstate)(\d+)$/', $key, $matches)) {
            $interfaceName = strtoupper($matches[1]).(int) $matches[2];
            if (defined(DiskInterface::class.'::'.$interfaceName)) {
                $interface = constant(DiskInterface::class.'::'.$interfaceName);
            }
        }

        // The 1/0 boolean flags and string identity fields map straight off the
        // attributes. Everything below needs bespoke handling the one-key-per-
        // property attribute model can't express: an unbacked enum, defensive
        // tryFrom() enums, a unit-suffixed size, dual-unit (mbps|bps) bandwidth,
        // and integer fields that fall back to 0 rather than null.
        [$mapped] = self::mapProxmoxProperties($pairs);

        $get = fn (string $k, $default = null) => data_get($pairs, $k, $default);

        // media is an unbacked enum defaulting to DISK, so it stays a match.
        $diskMediaType = match ($get('media')) {
            'cdrom' => DiskMediaType::CDROM,
            default => DiskMediaType::DISK,
        };

        // size carries an optional K/M/G/T unit suffix scaling it into bytes.
        $size = 0;
        if (filled($get('size')) && preg_match('/^(\d+)([KMGT])?$/', $get('size'), $sizeMatch)) {
            $sizeValue = (int) $sizeMatch[1];
            $size = match ($sizeMatch[2] ?? '') {
                'K' => $sizeValue * 1024,
                'M' => $sizeValue * 1024 * 1024,
                'G' => $sizeValue * 1024 * 1024 * 1024,
                'T' => $sizeValue * 1024 * 1024 * 1024 * 1024,
                default => $sizeValue,
            };
        }

        // These enums use tryFrom() (null/RAW on an unknown value) so an
        // unfamiliar PVE-version value degrades gracefully rather than throwing.
        $format = filled($get('format')) ? (DiskFormat::tryFrom($get('format')) ?? DiskFormat::RAW) : DiskFormat::RAW;
        $cacheMode = filled($get('cache')) ? DiskCacheMode::tryFrom($get('cache')) : null;
        $aioMode = filled($get('aio')) ? DiskAioMode::tryFrom($get('aio')) : null;
        $discardMode = filled($get('discard')) ? DiskDiscardMode::tryFrom($get('discard')) : null;
        $readErrorAction = filled($get('rerror')) ? DiskReadErrorAction::tryFrom($get('rerror')) : null;
        $writeErrorAction = filled($get('werror')) ? DiskWriteErrorAction::tryFrom($get('werror')) : null;
        $translationMode = filled($get('trans')) ? DiskTranslationMode::tryFrom($get('trans')) : null;

        // Bandwidth limits accept either a byte value or an mbps value (which
        // wins when both are present) that scales up to bytes.
        $bps = match (true) {
            filled($get('mbps')) => (int) ($get('mbps') * 1024 * 1024),
            filled($get('bps')) => (int) $get('bps'),
            default => null,
        };
        $bpsRead = match (true) {
            filled($get('mbps_rd')) => (int) ($get('mbps_rd') * 1024 * 1024),
            filled($get('bps_rd')) => (int) $get('bps_rd'),
            default => null,
        };
        $bpsWrite = match (true) {
            filled($get('mbps_wr')) => (int) ($get('mbps_wr') * 1024 * 1024),
            filled($get('bps_wr')) => (int) $get('bps_wr'),
            default => null,
        };
        $bpsMax = filled($get('mbps_max')) ? (int) ($get('mbps_max') * 1024 * 1024) : null;
        $bpsReadMax = filled($get('mbps_rd_max')) ? (int) ($get('mbps_rd_max') * 1024 * 1024) : null;
        $bpsWriteMax = filled($get('mbps_wr_max')) ? (int) ($get('mbps_wr_max') * 1024 * 1024) : null;

        // Length limits accept a *_max_length key or an older *_length alias.
        $bpsReadMaxLength = $get('bps_rd_max_length', $get('bps_rd_length'));
        $bpsReadMaxLength = filled($bpsReadMaxLength) ? (int) $bpsReadMaxLength : null;
        $bpsWriteMaxLength = $get('bps_wr_max_length', $get('bps_wr_length'));
        $bpsWriteMaxLength = filled($bpsWriteMaxLength) ? (int) $bpsWriteMaxLength : null;
        $iopsReadMaxLength = $get('iops_rd_max_length', $get('iops_rd_length'));
        $iopsReadMaxLength = filled($iopsReadMaxLength) ? (int) $iopsReadMaxLength : null;
        $iopsWriteMaxLength = $get('iops_wr_max_length', $get('iops_wr_length'));
        $iopsWriteMaxLength = filled($iopsWriteMaxLength) ? (int) $iopsWriteMaxLength : null;

        return new self(
            interface: $interface,
            volume: $volume,
            diskMediaType: $diskMediaType,
            size: $size,
            format: $format,
            cacheMode: $cacheMode,
            aioMode: $aioMode,
            discardMode: $discardMode,
            isEmulatingSSD: $mapped['isEmulatingSSD'] ?? false,
            isIncludedInBackup: $mapped['isIncludedInBackup'] ?? true,
            isReplicated: $mapped['isReplicated'] ?? true,
            isReadonly: $mapped['isReadonly'] ?? false,
            isIOThreadEnabled: $mapped['isIOThreadEnabled'] ?? false,
            bps: $bps,
            bpsMax: $bpsMax,
            bpsRead: $bpsRead,
            bpsReadMax: $bpsReadMax,
            bpsWrite: $bpsWrite,
            bpsWriteMax: $bpsWriteMax,
            iops: (int) $get('iops'),
            iopsMax: (int) $get('iops_max'),
            iopsRead: (int) $get('iops_rd'),
            iopsReadMax: (int) $get('iops_rd_max'),
            iopsWrite: (int) $get('iops_wr'),
            iopsWriteMax: (int) $get('iops_wr_max'),
            isSnapshot: $mapped['isSnapshot'] ?? false,
            isShared: $mapped['isShared'] ?? false,
            detectZeroes: $mapped['detectZeroes'] ?? false,
            readErrorAction: $readErrorAction,
            writeErrorAction: $writeErrorAction,
            translationMode: $translationMode,
            wwn: $mapped['wwn'] ?? null,
            bpsMaxLength: (int) $get('bps_max_length'),
            bpsReadMaxLength: $bpsReadMaxLength,
            bpsWriteMaxLength: $bpsWriteMaxLength,
            cylinders: (int) $get('cyls'),
            heads: (int) $get('heads'),
            iopsMaxLength: (int) $get('iops_max_length'),
            iopsReadMaxLength: $iopsReadMaxLength,
            iopsWriteMaxLength: $iopsWriteMaxLength,
            model: $mapped['model'] ?? null,
            product: $mapped['product'] ?? null,
            queues: (int) $get('queues'),
            isScsiBlock: $mapped['isScsiBlock'] ?? false,
            sectors: (int) $get('secs'),
            serial: $mapped['serial'] ?? null,
            vendor: $mapped['vendor'] ?? null,
        );
    }
}
