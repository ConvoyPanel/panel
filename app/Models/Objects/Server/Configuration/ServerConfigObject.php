<?php

namespace App\Models\Objects\Server\Configuration;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ServerConfigObject extends Data
{
    public function __construct(
        public array|null $boot_order,
        public array|null $disks,
        public bool|null $template,
        public AddressConfigObject|null $addresses,
        public bool|null $visible,
    ){}
}