<?php

namespace App\Data\Server;

use Spatie\LaravelData\Data;

class ConsoleCredentialsData extends Data
{
    public function __construct(
        public string $nodeFqdn,
        public int $nodePort,
        public ?string $nodePveName,
        public int $vmid,
        public array $credentials,
    ) {}
}
