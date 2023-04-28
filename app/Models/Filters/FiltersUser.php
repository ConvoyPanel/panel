<?php

namespace Convoy\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersUser implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $query->where('id', $value)
            ->orWhere('email', $value)
            ->orWhereRaw('LOWER(name) LIKE ?', ["%$value%"]);
    }
}
