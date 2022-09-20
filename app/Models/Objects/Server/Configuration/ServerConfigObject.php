<?php

namespace Convoy\Models\Objects\Server\Configuration;

use Convoy\Models\Objects\Server\Allocations\Storage\DiskObject;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Attributes\DataCollectionOf;

class ServerConfigObject extends Data
{
    public function __construct(
        public array|null $boot_order,
        #[DataCollectionOf(DiskObject::class)]
        public DataCollection|null $disks,
        public bool|null $template,
        public AddressConfigObject|null $addresses,
        public bool|null $visible,
    ){}
}