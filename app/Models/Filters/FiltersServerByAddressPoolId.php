<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersServerByAddressPoolId implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $query->whereRaw(
            "
                id IN (
                    SELECT serv.id 
                    FROM servers serv
                    JOIN address_pool_to_node apn ON serv.node_id = apn.node_id
                    WHERE apn.address_pool_id = ? 
                )
                ",
            [$value],
        );
    }
}
