<?php

namespace App\Data\Server;

use App\Data\Server\Proxmox\Config\DiskData;
use App\Enums\Server\Disk\DiskDiscardMode;
use App\Enums\Server\Disk\DiskFormat;
use App\Enums\Server\Disk\DiskMediaType;
use Spatie\LaravelData\Data;

/**
 * One storage device attached to a server, as the client screen needs it.
 *
 * A narrowed projection of {@see DiskData}, which carries ~50 properties of PVE
 * plumbing (wwn, serial, per-direction throttles, geometry). None of that is
 * actionable from the client, and some of it identifies host hardware, so the
 * device list ships the handful of fields that answer "what is this thing".
 */
class StorageDeviceData extends Data
{
    public function __construct(
        /** The PVE bus slot the device is attached to -- `sata0`, `ide2`. */
        public string $interface,
        /** Whether this is a disk or a virtual optical drive. */
        public DiskMediaType $media,
        /** The backing volume, e.g. `local-lvm:vm-104-disk-0`. Null on an empty drive. */
        public ?string $volume,
        /**
         * For an optical drive with an image in it, the image's file name --
         * the one thing about a cdrom a user recognises. Null when empty.
         */
        public ?string $mediaName,
        /**
         * Whether this is the cloud-init drive PVE synthesises rather than a
         * drive the user put anything in. It is technically a cdrom, so calling
         * it one is true and useless: it is the 4 MiB `ide` device that shows up
         * unexplained on nearly every server.
         */
        public bool $isCloudinitDrive,
        public int $size,
        public DiskFormat $format,
        public bool $isEmulatingSSD,
        public bool $isIncludedInBackup,
        public bool $isReadonly,
        public ?DiskDiscardMode $discardMode,
        public bool $isIOThreadEnabled,
    ) {}

    public static function fromDisk(DiskData $disk): self
    {
        // PVE writes an empty optical drive as `ide2: none,media=cdrom`, so
        // "none" is the absence of a volume rather than a volume named none.
        $volume = ($disk->volume === '' || $disk->volume === 'none') ? null : $disk->volume;
        $isCloudinit = $volume !== null && str_ends_with($volume, '-cloudinit');

        return new self(
            interface: $disk->interface->value,
            media: $disk->diskMediaType,
            volume: $volume,
            mediaName: $isCloudinit ? null : self::mediaNameFor($disk->diskMediaType, $volume),
            isCloudinitDrive: $isCloudinit,
            size: $disk->size,
            format: $disk->format,
            isEmulatingSSD: $disk->isEmulatingSSD,
            isIncludedInBackup: $disk->isIncludedInBackup,
            isReadonly: $disk->isReadonly,
            discardMode: $disk->discardMode,
            isIOThreadEnabled: $disk->isIOThreadEnabled,
        );
    }

    /**
     * An ISO volume reads `storage:iso/debian-13.iso`; the part after the last
     * slash is the file name the ISO library lists it under.
     */
    private static function mediaNameFor(DiskMediaType $media, ?string $volume): ?string
    {
        if ($media !== DiskMediaType::CDROM || $volume === null) {
            return null;
        }

        $name = str_contains($volume, '/') ? substr(strrchr($volume, '/'), 1) : $volume;

        return $name === '' ? null : $name;
    }
}
