<?php

namespace Convoy\Models\Filters;

use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AllowedNullableFilter extends AllowedFilter
{
    public function filter(QueryBuilder $query, $value): void
    {
        $valueToFilter = $this->resolveValueForFiltering($value);

        ($this->filterClass)($query->getEloquentBuilder(), $valueToFilter, $this->internalName);
    }
}
