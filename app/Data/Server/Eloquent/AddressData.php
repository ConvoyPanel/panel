<?php

namespace Convoy\Data\Server\Eloquent;

use Spatie\LaravelData\Data;

class AddressData extends Data
{
    public function __construct(
      public int $id,
      public string $address,
      public int $cidr,
      public string $gateway,
      public ?string $mac_address,
    ) {}
}
