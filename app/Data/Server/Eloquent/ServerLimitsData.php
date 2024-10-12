<?php

namespace Convoy\Data\Server\Eloquent;

use Spatie\LaravelData\Data;

class ServerLimitsData extends Data
{
    public function __construct(
        public int $cpu,
        public int $memory,
        public int $disk,
        public ?int $snapshots,
        public ?int $backups,
        public ?int $bandwidth,
        public ServerAddressesData $addresses,
        public ?string $mac_address,
    ) {
    }
}
