<?php

namespace Convoy\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersUserWildcard implements Filter
{
    /**
     * An id, or part of a name or email address, in any case. Email used to
     * match only in full, and the name was lowercased but the search wasn't,
     * so "Jane" didn't find Jane.
     */
    public function __invoke(Builder $query, $value, string $property): void
    {
        $value = Str::lower(trim((string) $value));
        $like = '%'.addcslashes($value, '%_\\').'%';

        // Grouped, so the OR doesn't escape any other constraint on the query.
        $query->where(function (Builder $query) use ($value, $like) {
            if (ctype_digit($value)) {
                $query->orWhere('id', (int) $value);
            }

            $query->orWhereRaw('LOWER(email) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
        });
    }
}
