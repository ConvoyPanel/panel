<?php

namespace App\Data\Server;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One extra data disk remembered by a preset. `size` is in GiB, the unit the
 * create form asks for — see ServerPresetSettingsData.
 */
#[MapInputName(SnakeCaseMapper::class)]
class ServerPresetDiskData extends Data
{
    public function __construct(
        public int $storageId,
        public float $size,
    ) {}
}
