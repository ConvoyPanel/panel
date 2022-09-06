<?php

namespace App\Models\Objects\Server\Configuration;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ServerConfigurationObject extends Data
{
    public function __construct(
        public array|Optional $boot_order,
        public array|Optional $disks,
        public bool|Optional $template,
        public AddressConfigurationObject|Optional $addresses,
        public bool|Optional $visible,
    ){}
}