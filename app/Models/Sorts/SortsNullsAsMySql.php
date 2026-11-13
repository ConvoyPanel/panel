<?php

namespace Convoy\Models\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

/**
 * Sorts a nullable column with its nulls where MySQL put them: first when
 * ascending, last when descending. Postgres does the opposite by default, so a
 * backup that hasn't finished (completed_at null) would jump to the other end of
 * the list.
 */
class SortsNullsAsMySql implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $column = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($property));

        $query->orderByRaw($column.($descending ? ' DESC NULLS LAST' : ' ASC NULLS FIRST'));
    }
}
