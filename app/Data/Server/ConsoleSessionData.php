<?php

namespace App\Data\Server;

use App\Enums\Server\ConsoleType;
use Spatie\LaravelData\Data;

class ConsoleSessionData extends Data
{
    public function __construct(
        public string $url,
        public string $token,
        public int $protocol,
        public ConsoleType $type,
        public ?string $password,
    ) {}
}
