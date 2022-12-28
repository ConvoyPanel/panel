<?php

namespace Convoy\Data\Server\Eloquent;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IP;

class AddressData extends Data
{
    public function __construct(
      public int $id,
      #[In(['ipv4', 'ipv6'])]
      public string $type,
      #[IP]
      public string $address,
      public int $cidr,
      public string $gateway,
      public ?string $mac_address,
    ) {}
}
