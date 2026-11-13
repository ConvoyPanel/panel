<?php

namespace Convoy\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\FiltersExact;

/**
 * AllowedFilter::exact() for a bigint id column.
 *
 * MySQL cast `filter[node_id]=abc` to a number and matched nothing; Postgres
 * refuses to compare a bigint with text. Values that can't be ids are dropped
 * here, and a filter left with none matches nothing, as before. A null value
 * (an empty `->nullable()` filter) still means IS NULL.
 */
class FiltersExactId extends FiltersExact
{
    public function __invoke(Builder $query, $value, string $property)
    {
        if (is_null($value)) {
            return parent::__invoke($query, $value, $property);
        }

        $ids = array_values(array_filter(
            array_map(SearchTerm::id(...), (array) $value),
            fn (?int $id) => ! is_null($id),
        ));

        if ($ids === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        return parent::__invoke($query, is_array($value) ? $ids : $ids[0], $property);
    }
}
