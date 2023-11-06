<?php

namespace Convoy\Casts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MebibytesToAndFromBytes implements CastsAttributes
{
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
