<?php

namespace App\Data\Server;

use Spatie\LaravelData\Data;

class ServerNetworkSettingsData extends Data
{
    public function __construct(
        /** @var array<int, string> */
        public array $nameservers,
    ) {}
}
