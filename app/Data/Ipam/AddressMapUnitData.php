<?php

namespace App\Data\Ipam;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** One cell of the address map: what sits at this position in the block's run of units. */
#[MapInputName(SnakeCaseMapper::class)]
class AddressMapUnitData extends Data
{
    public function __construct(
        /** Position in the block, counting from zero — the cell's place on the grid. */
        public int $index,
        /** 'available' | 'assigned' | 'reserved' | 'system' | 'ungenerated'. */
        public string $state,
        /** Null where no address row exists yet: the unit is real, the record is not. */
        public ?string $ip,
        public ?int $addressId,
        public ?string $serverName,
    ) {}
}
