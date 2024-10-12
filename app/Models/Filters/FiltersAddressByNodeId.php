<?php

namespace Convoy\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersAddressByNodeId implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $query->whereRaw(
            '
                address_pool_id IN (
                    SELECT apn.address_pool_id
                    FROM address_pool_to_node apn
                    WHERE apn.node_id = ?
                )
            ',
            [$value],
        );
    }
}
