<?php

namespace App\Models\Concerns;

use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
use App\Models\AddressBlock;
use Illuminate\Database\Eloquent\Builder;

/**
 * Address counts by state, for anything that has an `addresses` relation.
 *
 * Shared by AddressBlock and AddressBlockGroup so a block's meter and the meter on the pool row
 * above it are computed the same way and cannot disagree. The aliases match the property names
 * AddressCapacityData reads, so adding a state means touching this file and that DTO only.
 */
trait CountsAddressStates
{
    /**
     * The `withCount`/`loadCount` map. Kept as a static so `show()` can load the same counts
     * onto a single model that `index()` selects for a page of them.
     *
     * `reserved` deliberately excludes system reservations: network, broadcast and gateway are
     * not operator decisions and cannot be released, so a meter that lumps them together tells
     * an operator they are holding addresses they never held.
     *
     * `$denseOnly` restricts the counts to blocks small enough to be materialized. A pool that
     * holds one sparse block alongside a /24 would otherwise count the v6 addresses minted into
     * that block against the /24's denominator, and read past 100% full. The sparse blocks are
     * reported separately instead, as a count of blocks the meter cannot measure.
     *
     * @return array<string, callable(Builder): Builder>
     */
    public static function addressStateCounts(bool $denseOnly = false): array
    {
        $scope = fn (Builder $query) => $denseOnly
            ? $query->whereRaw(
                'address_blocks.prefix_length_to - address_blocks.prefix_length_from <= ?',
                [AddressBlock::DENSE_MAX_HOST_BITS],
            )
            : $query;

        return [
            'addresses' => fn (Builder $query) => $scope($query),
            'addresses as assigned_addresses_count' => fn (Builder $query) => $scope($query)
                ->where('state', AddressState::Assigned),
            'addresses as reserved_addresses_count' => fn (Builder $query) => $scope($query)
                ->where('state', AddressState::Reserved)
                ->where(fn (Builder $inner) => $inner
                    ->whereNull('state_reason')
                    ->orWhere('state_reason', '!=', AddressStateReason::System)),
            'addresses as system_addresses_count' => fn (Builder $query) => $scope($query)
                ->where('state', AddressState::Reserved)
                ->where('state_reason', AddressStateReason::System),
            'addresses as available_addresses_count' => fn (Builder $query) => $scope($query)
                ->where('state', AddressState::Available),
        ];
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeWithAddressStateCounts(Builder $query, bool $denseOnly = false): void
    {
        $query->withCount(static::addressStateCounts($denseOnly));
    }
}
