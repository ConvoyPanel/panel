<?php

namespace App\Models\Objects\Server\Configuration;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ServerConfigurationObject extends Data
{
    public function __construct(
        public array|null $boot_order,
        public array|null $disks,
        public bool|null $template,
        public AddressConfigurationObject|null $addresses,
        public bool|null $visible,
    ){}
}