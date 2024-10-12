<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersServerWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $query->where('id', $value)
              ->orWhere('uuid', $value)
              ->orWhere('uuid_short', $value)
              ->orWhereRaw('LOWER(hostname) LIKE ?', ["%$value%"])
              ->orWhereRaw('LOWER(name) LIKE ?', ["%$value%"]);
    }
}
