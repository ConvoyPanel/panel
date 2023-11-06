<?php

namespace Convoy\Models\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FiltersNode implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $query->where('id', $value)
            ->orWhereRaw('LOWER(fqdn) LIKE ?', ["%$value%"])
            ->orWhereRaw('LOWER(name) LIKE ?', ["%$value%"]);
    }
}
