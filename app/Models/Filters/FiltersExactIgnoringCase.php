<?php

namespace Convoy\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * AllowedFilter::exact() for a text column, ignoring case as MySQL's collation
 * did: `filter[email]=Bob@Example.com` found bob@example.com there.
 */
class FiltersExactIgnoringCase implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $column = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($property));
        $values = array_map(SearchTerm::lower(...), (array) $value);

        if (count($values) === 1) {
            $query->whereRaw("LOWER({$column}) = ?", $values);

            return;
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $query->whereRaw("LOWER({$column}) IN ({$placeholders})", $values);
    }
}
