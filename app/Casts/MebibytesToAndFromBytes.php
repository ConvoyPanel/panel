<?php

namespace Convoy\Casts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MebibytesToAndFromBytes implements CastsAttributes
{
    /**
     * The largest size these columns take, in bytes: 2,147,483,647 MiB, Postgres's
     * integer limit. 5.x stores them in Postgres, so anything larger couldn't be
     * imported there, although MySQL's unsigned int holds twice as much.
     */
    public const MAX_BYTES = 2147483647 * 1048576;

    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return !is_null($value) ? $value * 1048576 : $value; // Convert from megabytes to bytes
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return !is_null($value) ? intval(floor($value / 1048576)) : $value; // Convert from bytes to megabytes to prevent overflow
    }
}
