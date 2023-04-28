<?php

namespace Convoy\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class MebibytesToAndFromBytes implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  int|null  $value
     * @param  array  $attributes
     * @return ?int
     */
    public function get(Model $model, string $key, mixed $value, array $attributes)
    {
        return isset($value) ? $value * 1048576 : $value; // Convert from megabytes to bytes
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  int|null  $value
     * @param  array  $attributes
     * @return ?int
     */
    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        return isset($value) ? intval(floor($value / 1048576)) : $value; // Convert from bytes to megabytes to prevent overflow
    }
}
