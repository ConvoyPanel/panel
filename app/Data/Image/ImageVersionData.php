<?php

namespace App\Data\Image;

use App\Models\ImageVersion;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ImageVersionData extends Data
{
    public function __construct(
        public string $uuid,
        public string $version,
        /** @var DataCollection<int, ImageDiskData> */
        public DataCollection $disks,
        /** Total bytes a node has to transfer for this version. */
        public int $size,
        /** The provisioned size of the system disk: the smallest plan that fits. */
        public int $minimumDisk,
        public bool $isActive,
    ) {}

    public static function fromModel(ImageVersion $version): self
    {
        return new self(
            uuid: $version->uuid,
            version: $version->version,
            disks: ImageDiskData::collect($version->diskSet()->all(), DataCollection::class),
            size: (int) $version->size_bytes,
            minimumDisk: $version->minimumDiskSize(),
            isActive: (bool) $version->is_active,
        );
    }
}
