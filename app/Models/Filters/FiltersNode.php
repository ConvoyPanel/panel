<?php

namespace Convoy\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersNode implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $query->where('id', $value)
            ->orWhereRaw('LOWER(fqdn) LIKE ?', ["%$value%"])
            ->orWhereRaw('LOWER(name) LIKE ?', ["%$value%"]);
    }
}