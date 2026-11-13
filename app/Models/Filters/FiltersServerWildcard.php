<?php

namespace Convoy\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersServerWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        // Grouped, so the ORs can't escape a constraint the controller applied first.
        $query->where(function (Builder $query) use ($value) {
            if (! is_null($id = SearchTerm::id($value))) {
                $query->orWhere('id', $id);
            }

            // Both uuids are stored lowercase.
            $query->orWhere('uuid', SearchTerm::lower($value))
                  ->orWhere('uuid_short', SearchTerm::lower($value))
                  ->orWhereRaw('LOWER(hostname) LIKE ?', [SearchTerm::contains($value)])
                  ->orWhereRaw('LOWER(name) LIKE ?', [SearchTerm::contains($value)]);
        });
    }
}
