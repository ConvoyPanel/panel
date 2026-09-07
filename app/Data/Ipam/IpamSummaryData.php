<?php

namespace App\Data\Ipam;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The IPAM index's headline figures, across every pool rather than the page of pools on screen.
 *
 * Summing the visible page would be wrong the moment a second page exists, and a headline number
 * that quietly means "of the fifty rows you can see" is worse than no headline at all.
 */
#[MapInputName(SnakeCaseMapper::class)]
class IpamSummaryData extends Data
{
    public function __construct(
        public AddressCapacityData $capacity,
        public int $poolsCount,
        public int $blocksCount,
        public int $nodesCount,
        /** Dense blocks at or above 90% of usable — the ones about to stop being able to hand out. */
        public int $blocksNearlyFull,
        /** The fullest of them, so the tile names something an operator can act on. */
        public ?NearlyFullBlockData $fullestBlock,
    ) {}
}
