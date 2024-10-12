<?php

namespace Convoy\Data\Server;

use Spatie\LaravelData\Data;

class MacAddressData extends Data
{
    public function __construct(
        public ?string $proxmox,
        public ?string $eloquent,
    ) {
    }
}
