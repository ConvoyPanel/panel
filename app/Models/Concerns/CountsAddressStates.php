<?php

namespace App\Models\Concerns;

use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
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
     * @return array<string, callable(Builder): Builder>
     */
    public static function addressStateCounts(): array
    {
        return [
            'addresses',
            'addresses as assigned_addresses_count' => fn (Builder $query) => $query
                ->where('state', AddressState::Assigned),
            'addresses as reserved_addresses_count' => fn (Builder $query) => $query
                ->where('state', AddressState::Reserved)
                ->where(fn (Builder $inner) => $inner
                    ->whereNull('state_reason')
                    ->orWhere('state_reason', '!=', AddressStateReason::System)),
            'addresses as system_addresses_count' => fn (Builder $query) => $query
                ->where('state', AddressState::Reserved)
                ->where('state_reason', AddressStateReason::System),
            'addresses as available_addresses_count' => fn (Builder $query) => $query
                ->where('state', AddressState::Available),
        ];
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeWithAddressStateCounts(Builder $query): void
    {
        $query->withCount(static::addressStateCounts());
    }
}
