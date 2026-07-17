<?php

namespace App\Data\Anchor;

use Spatie\LaravelData\Data;

class AnchorEnrollmentData extends Data
{
    public function __construct(
        public string $token,
        public string $command,
        public string $expiresAt,
    ) {}
}
