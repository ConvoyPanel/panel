<?php

namespace App\Extensions\Spatie\Data;

use Carbon\CarbonInterval;
use InvalidArgumentException;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Transformation\TransformationContext;
use Spatie\LaravelData\Transformers\Transformer;

class CarbonIntervalTransformer implements Transformer
{
    public function transform(DataProperty $property, mixed $value, TransformationContext $context): int
    {
        if (! ($value instanceof CarbonInterval)) {
            throw new InvalidArgumentException('Value must be a CarbonInterval instance');
        }

        return (int) $value->total('seconds');
    }
}
