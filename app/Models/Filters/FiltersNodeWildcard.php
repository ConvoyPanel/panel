<?php

namespace Convoy\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersNodeWildcard implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        // Grouped, so the ORs can't escape a constraint the controller applied first
        // (the nodes of one address pool or one Coterm).
        $query->where(function (Builder $query) use ($value) {
            if (! is_null($id = SearchTerm::id($value))) {
                $query->orWhere('id', $id);
            }

            $query->orWhereRaw('LOWER(fqdn) LIKE ?', [SearchTerm::contains($value)])
                  ->orWhereRaw('LOWER(name) LIKE ?', [SearchTerm::contains($value)]);
        });
    }
}
