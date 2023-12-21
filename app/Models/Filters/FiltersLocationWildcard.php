<?php

namespace Convoy\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersLocationWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $query->where('id', $value)
              ->orWhereRaw('LOWER(short_code) LIKE ?', ["%$value%"]);
    }
}
