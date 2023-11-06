<?php

namespace Convoy\Models\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FiltersUser implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $query->where('id', $value)
            ->orWhere('email', $value)
            ->orWhereRaw('LOWER(name) LIKE ?', ["%$value%"]);
    }
}
