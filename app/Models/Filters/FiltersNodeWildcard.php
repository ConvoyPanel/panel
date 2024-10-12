<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersNodeWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $query->where('id', $value)
              ->orWhereRaw('LOWER(fqdn) LIKE ?', ["%$value%"])
              ->orWhereRaw('LOWER(name) LIKE ?', ["%$value%"]);
    }
}
