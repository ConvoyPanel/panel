<?php

namespace App\Data\Ipam;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** Enough of a block to name it and link to it from the IPAM index's summary tile. */
#[MapInputName(SnakeCaseMapper::class)]
class NearlyFullBlockData extends Data
{
    public function __construct(
        public int $id,
        public int $addressBlockGroupId,
        /** The block's CIDR — what an operator recognises it by. */
        public string $label,
        public float $percent,
    ) {}
}
