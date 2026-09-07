<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * The panel's size convention, in one place: **the database stores mebibytes,
 * PHP and the API speak bytes.**
 *
 * Sizes are held as MiB on disk because a byte count of a large pool overflows
 * a signed 32-bit integer, and every consumer -- validation rules, DTOs, the
 * frontend forms -- works in bytes. So a column named `size` or
 * `reserved_bytes` is MiB in Postgres and bytes the moment it is read through
 * a model. Neither name is wrong; they describe different sides of this cast.
 *
 * Two things follow, and both have bitten:
 *
 *  - `withSum()` and any raw query bypass casts entirely, so an aggregate over
 *    these columns is in MiB and has to be scaled by hand. {@see Storage} does
 *    exactly that, and it is the one place the convention is applied manually.
 *  - Rounding is lossy in one direction: `set()` floors to whole MiB. A column
 *    whose exact byte value matters -- a floor a plan must clear, a size
 *    compared against a file -- must opt out and say so in its name, the way
 *    `image_versions.size_bytes` does.
 */
class StorageSizeCast implements CastsAttributes
{
    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        return $value >= 0 ? $value * 1048576 : -1; // Convert from megabytes to bytes
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        return $value >= 0 ? intval(
            floor($value / 1048576),
        ) : -1; // Convert from bytes to megabytes to prevent overflow
    }
}
