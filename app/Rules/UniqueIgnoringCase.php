<?php

namespace Convoy\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Laravel's `unique` rule, compared case-insensitively.
 *
 * On MySQL the columns' utf8mb4_unicode_ci collation made `unique:users,email`
 * refuse "Bob@example.com" when "bob@example.com" existed, and the unique index
 * backed it up. Postgres compares exactly, so without this the panel would
 * accept both, and they would be two accounts. Model::getRules() turns every
 * `unique:table,column` string into one of these.
 */
class UniqueIgnoringCase implements ValidationRule
{
    private mixed $ignore = null;

    private string $ignoreColumn = 'id';

    public function __construct(
        private readonly string $table,
        private readonly string $column,
    ) {
    }

    /** Leave the row being updated out of the check, as Rule::unique()->ignore() does. */
    public function ignore(mixed $id, string $column = 'id'): self
    {
        $this->ignore = $id;
        $this->ignoreColumn = $column;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return;
        }

        $column = DB::getQueryGrammar()->wrap($this->column);

        $taken = DB::table($this->table)
            ->whereRaw("LOWER({$column}) = ?", [mb_strtolower((string) $value)])
            ->when(! is_null($this->ignore), fn ($query) => $query->where($this->ignoreColumn, '!=', $this->ignore))
            ->exists();

        if ($taken) {
            $fail('validation.unique')->translate();
        }
    }
}
