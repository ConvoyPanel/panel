<?php

namespace Convoy\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class StorageSizeCast implements CastsAttributes
{
    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return $value >= 0 ? $value * 1048576 : -1; // Convert from megabytes to bytes
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return $value >= 0 ? intval(
            floor($value / 1048576),
        ) : -1; // Convert from bytes to megabytes to prevent overflow
    }
}
