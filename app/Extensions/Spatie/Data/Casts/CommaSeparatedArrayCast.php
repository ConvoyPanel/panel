<?php

namespace App\Extensions\Spatie\Data\Casts;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class CommaSeparatedArrayCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): array
    {
        if (blank($value)) {
            return [];
        }

        return explode(',', $value);
    }
}
