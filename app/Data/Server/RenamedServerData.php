<?php

namespace App\Data\Server;

use App\Models\Server;
use Spatie\LaravelData\Data;

class RenamedServerData extends Data
{
    public function __construct(
        public string $name,
        public string $hostname,
    ) {}

    public static function fromModel(Server $server): self
    {
        return new self(
            name: $server->name,
            hostname: $server->hostname,
        );
    }
}
