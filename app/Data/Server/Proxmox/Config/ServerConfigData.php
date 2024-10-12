<?php

namespace Convoy\Data\Server\Proxmox\Config;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ServerConfigData extends Data
{
    public function __construct(
        public string $mac_address,
        #[DataCollectionOf(DiskData::class)]
        public DataCollection $boot_order,
        #[DataCollectionOf(DiskData::class)]
        public DataCollection $disks,
        /* public AddressConfigData $addresses */
    ) {
    }
}
