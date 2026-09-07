<?php

namespace App\Data\Image;

use App\Enums\Image\ImageDiskRole;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One disk belonging to an image version.
 *
 * Exactly one of `url` and `path` is set: `url` is an origin the operator gave
 * us, `path` is a file they uploaded and the panel serves from a Laravel disk.
 * Both answer the same question -- what URL returns these bytes -- so the node
 * never learns which one it got, and `sha256` makes either provable.
 */
#[MapInputName(SnakeCaseMapper::class)]
class ImageDiskData extends Data
{
    public function __construct(
        /** The `qm` slot this disk becomes, e.g. `scsi0` or `efidisk0`. */
        public string $slot,
        public ImageDiskRole $role,
        /** A public origin, or null when the panel is serving the file itself. */
        public ?string $url,
        /** Path on the images filesystem disk, or null when `url` is set. */
        public ?string $path,
        public string $sha256,
        /** Bytes on the wire -- what a node has to transfer. Never mebibytes:
         * this is compared against the length of a file a node downloads. */
        public int $size,
        /**
         * Provisioned size once imported, in bytes. The floor a plan's disk must
         * clear, so it is never rounded: a MiB of slack here is a build that
         * fails at import.
         */
        public int $virtualSize,
        public string $format = 'qcow2',
    ) {}

    public function isSystem(): bool
    {
        return $this->role === ImageDiskRole::SYSTEM;
    }

    public function isHosted(): bool
    {
        return filled($this->path);
    }
}
