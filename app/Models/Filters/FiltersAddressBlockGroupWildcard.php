<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersAddressBlockGroupWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        if ($value === '') {
            return;
        }

        $query->where(function (Builder $query) use ($value) {
            $query->where('name', 'LIKE', "%$value%")
                ->orWhere('description', 'LIKE', "%$value%");
        });
    }
}
