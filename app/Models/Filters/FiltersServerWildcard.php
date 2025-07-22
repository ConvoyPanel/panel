<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use function strtolower;

class FiltersServerWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        if ($value === '') {
            return;
        }

        $query->where(function (Builder $query) use ($value) {
            $query->whereRaw('LOWER(hostname) LIKE ?', ['%' . strtolower($value) . '%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($value) . '%'])
                  ->orWhereRaw('LOWER(uuid) LIKE ?', ['%' . strtolower($value) . '%'])
                  ->orWhereRaw('LOWER(uuid_short) LIKE ?', ['%' . strtolower($value) . '%']);
        });
    }
}
