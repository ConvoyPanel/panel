<?php

namespace Convoy\Models\Filters;

/**
 * Turns a search box value into what the wildcard filters compare against.
 *
 * MySQL forgave two things Postgres does not. It compared case-insensitively
 * (utf8mb4_unicode_ci), so `LOWER(name) LIKE '%Web%'` matched "web-1"; Postgres
 * needs the term lowercased too. And it cast text to a number when comparing it to
 * an id, so `id = 'web'` simply matched nothing; Postgres refuses the query.
 */
final class SearchTerm
{
    /** Bigint ids top out at 19 digits; anything longer can't be one. */
    private const MAX_ID_DIGITS = 18;

    /** The value as an id, or null when it can't be one. */
    public static function id(mixed $value): ?int
    {
        $value = trim((string) $value);

        return ctype_digit($value) && strlen($value) <= self::MAX_ID_DIGITS ? (int) $value : null;
    }

    /** The value lowercased, for comparing against LOWER(column). */
    public static function lower(mixed $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    /** A LIKE pattern matching columns that contain the value, for LOWER(column) LIKE ?. */
    public static function contains(mixed $value): string
    {
        return '%'.self::lower($value).'%';
    }
}
