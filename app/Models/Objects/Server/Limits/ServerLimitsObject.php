<?php

namespace Convoy\Models\Objects\Server\Limits;

use Spatie\LaravelData\Data;

class ServerLimitsObject extends Data
{
    public function __construct(
        public int|null $cpu,
        public int|null $memory,
        public array|null $address_ids,
        public int|null $disk,
        public int|null $snapshot_limit,
        public int|null $backup_limit,
        public int|null $bandwidth_limit,
        public AddressLimitsObject|null $addresses,
        public string|null $mac_address,
    ) {
    }
}
