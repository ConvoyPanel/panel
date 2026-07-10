<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class SSOTokenData extends Data
{
    public function __construct(
        public int $userId,
        /** Absolute, single-use, expiring signed URL the integration redirects the browser to. */
        public string $link,
    ) {}
}
