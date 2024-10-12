<?php

namespace App\Data\Server\Eloquent;

use Spatie\LaravelData\Data;

class ServerEloquentData extends Data
{
    public function __construct(
        public int              $id,
        public string           $uuid_short,
        public string           $uuid,
        public int              $node_id,
        public string           $hostname,
        public string           $name,
        public ?string          $description,
        public ?string          $status,
        public ServerUsagesData $usages,
        public ServerLimitsData $limits,
    ) {
    }
}
