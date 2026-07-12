<?php

namespace App\Casts;

use App\Data\Server\OveragePenaltyData;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts a nullable JSON `overage_penalty` column to/from {@see OveragePenaltyData}.
 * Null (the "inherit up the cascade" state) round-trips as null.
 *
 * @implements CastsAttributes<OveragePenaltyData|null, OveragePenaltyData|array<string, mixed>|null>
 */
class OveragePenaltyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?OveragePenaltyData
    {
        if ($value === null) {
            return null;
        }

        return OveragePenaltyData::from(json_decode($value, true));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $penalty = $value instanceof OveragePenaltyData
            ? $value
            : OveragePenaltyData::from($value);

        return $penalty->toJson();
    }
}
