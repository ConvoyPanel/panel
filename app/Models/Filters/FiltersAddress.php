<?php

namespace Convoy\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersAddress implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        if (gettype($value) === 'array') {
            $query->whereIn('id', $value)
                ->orWhereIn('address', $value)
                ->orWhereIn('mac_address', $value);
        } else {
            $query->where('id', $value)
                ->orWhere('address', $value)
                ->orWhere('mac_address', $value);
        }
    }
}
