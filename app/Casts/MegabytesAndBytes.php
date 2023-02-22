<?php

namespace Convoy\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MegabytesAndBytes implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  int|null  $value
     * @param  array  $attributes
     * @return ?int
     */
    public function get($model, $key, $value, $attributes)
    {
        return isset($value) ? $value * 1048576 : $value; // Convert from megabytes to bytes
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  int|null  $value
     * @param  array  $attributes
     * @return ?int
     */
    public function set($model, $key, $value, $attributes)
    {
        return isset($value) ? intval(floor($value / 1048576)) : $value; // Convert from bytes to megabytes to prevent overflow
    }
}
