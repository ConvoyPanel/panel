<?php

namespace Convoy\Models\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FiltersServer implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $query->where('id', $value)
            ->orWhere('uuid', $value)
            ->orWhere('uuid_short', $value)
            ->orWhereRaw('LOWER(hostname) LIKE ?', ["%$value%"])
            ->orWhereRaw('LOWER(name) LIKE ?', ["%$value%"]);
    }
}
