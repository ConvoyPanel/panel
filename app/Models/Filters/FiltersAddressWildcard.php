<?php

namespace Convoy\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersAddressWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        // The query builder splits a value on commas, so this gets a list for "a,b".
        $values = Arr::wrap($value);
        $ids = array_values(array_filter(array_map(SearchTerm::id(...), $values), fn ($id) => ! is_null($id)));
        // Addresses and MACs are stored lowercase.
        $lowered = array_map(SearchTerm::lower(...), $values);

        // Grouped, so the ORs can't escape the node or pool the controller scoped
        // the list to.
        $query->where(function (Builder $query) use ($ids, $lowered) {
            if ($ids !== []) {
                $query->orWhereIn('id', $ids);
            }

            $query->orWhereIn('address', $lowered)
                  ->orWhereIn('mac_address', $lowered);
        });
    }
}
