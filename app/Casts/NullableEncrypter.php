<?php

namespace App\Casts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class NullableEncrypter implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return !empty($value) ? app(Encrypter::class)->decrypt($value) : null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return !empty($value) ? app(Encrypter::class)->encrypt($value) : null;
    }
}
