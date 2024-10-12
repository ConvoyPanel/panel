<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersUserWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $query->where('id', $value)
              ->orWhere('email', $value)
              ->orWhereRaw('LOWER(name) LIKE ?', ["%$value%"]);
    }
}
