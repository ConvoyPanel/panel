<?php

namespace Convoy\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersAddressPoolWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $query->where('id', $value)
              ->orWhereRaw('LOWER(name) LIKE ?', ["%$value%"]);
    }
}
