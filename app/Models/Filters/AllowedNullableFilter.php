<?php

namespace Convoy\Models\Filters;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class AllowedNullableFilter extends AllowedFilter
{
    public function filter(QueryBuilder $query, $value): void
    {
        $valueToFilter = $this->resolveValueForFiltering($value);

        ($this->filterClass)($query->getEloquentBuilder(), $valueToFilter, $this->internalName);
    }
}
