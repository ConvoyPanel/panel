<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersAddressWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        if (is_array($value)) {
            $query->whereIn('id', $value)
                  ->orWhereIn('address', Arr::map($value, fn ($v) => strtolower($v)))
                  ->orWhereIn('mac_address', Arr::map($value, fn ($v) => strtolower($v)));
        } else {
            $query->where('id', $value)
                  ->orWhere('address', strtolower($value))
                  ->orWhere('mac_address', strtolower($value));
        }
    }
}
