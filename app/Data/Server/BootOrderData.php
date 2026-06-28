<?php

namespace App\Data\Server;

use App\Data\Server\Proxmox\Config\DiskData;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class BootOrderData extends Data
{
    public function __construct(
        /** @var DataCollection<int, DiskData> */
        public DataCollection $unusedDevices,
        /** @var DataCollection<int, DiskData> */
        public DataCollection $bootOrder,
    ) {}
}
