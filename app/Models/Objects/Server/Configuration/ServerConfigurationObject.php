<?php

namespace App\Models\Objects\Server\Configuration;

use Spatie\LaravelData\Data;

class ServerConfigurationObject extends Data
{
    public function __construct(
        public bool $template,
    ){}
}