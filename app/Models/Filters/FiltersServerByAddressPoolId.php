<?php

namespace Convoy\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersServerByAddressPoolId implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        // MySQL matched nothing for an id that isn't a number; Postgres would refuse
        // the query, so say "nothing" without asking.
        if (is_null($poolId = SearchTerm::id($value))) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereRaw(
            '
                id IN (
                    SELECT serv.id
                    FROM servers serv
                    JOIN address_pool_to_node apn ON serv.node_id = apn.node_id
                    WHERE apn.address_pool_id = ?
                )
            ',
            [$poolId],
        );
    }
}
