<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

use function ctype_digit;
use function strtolower;

class FiltersUserWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        if ($value === '') {
            return;
        }

        // Grouped, so these alternatives widen only this filter rather than
        // escaping alongside every other constraint on the query.
        $query->where(function (Builder $query) use ($value) {
            // `id` is a bigint: on Postgres, comparing it against a term that
            // is not a number is an error rather than a miss, so a search for
            // a name used to 500 instead of matching it.
            if (ctype_digit((string) $value)) {
                $query->orWhere('id', $value);
            }

            $query->orWhereRaw('LOWER(email) LIKE ?', ['%'.strtolower($value).'%'])
                ->orWhereRaw('LOWER(name) LIKE ?', ['%'.strtolower($value).'%']);
        });
    }
}
