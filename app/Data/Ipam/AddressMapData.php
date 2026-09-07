<?php

namespace App\Data\Ipam;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Every unit of a block, in address order, so the UI can draw the address space rather than
 * paginate it.
 *
 * A block is only drawable when it is bounded and small enough to fit on a screen as cells; the
 * two flags say which of the reasons applies rather than returning an empty grid the reader has
 * to interpret.
 */
#[MapInputName(SnakeCaseMapper::class)]
class AddressMapData extends Data
{
    /**
     * Above this the grid stops being a picture — a /19 of /32s is already 8,192 cells, past what
     * fits on screen at a legible size, and the list with its filters is the better tool. Well
     * under DENSE_MAX_HOST_BITS, which bounds what is materialized at all.
     */
    public const MAX_UNITS = 4096;

    public function __construct(
        /** Minted on demand; there is no bounded run of units to draw. */
        public bool $sparse,
        /** Bounded, but past what a grid can usefully show. */
        public bool $tooLarge,
        public ?int $totalUnits,
        /** @var array<int, AddressMapUnitData> */
        public array $units,
    ) {}
}
