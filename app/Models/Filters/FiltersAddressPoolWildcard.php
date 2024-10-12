<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersAddressPoolWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $query->where('id', $value)
              ->orWhereRaw('LOWER(name) LIKE ?', ["%$value%"]);
    }
}
