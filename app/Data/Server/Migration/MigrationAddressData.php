<?php

namespace App\Data\Server\Migration;

use App\Data\Ipam\IpamAddressData;
use App\Enums\Network\AddressVersion;
use App\Models\Address;
use Spatie\LaravelData\Data;

/**
 * An address as a migration preview needs to show it: the value, its pool, and
 * nothing else. {@see IpamAddressData} is the full IPAM row and
 * carries state, reason and a lazy server; a confirmation dialog wants a line
 * of text.
 */
class MigrationAddressData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $ip,
        public readonly int $prefixLength,
        public readonly AddressVersion $version,
        public readonly ?string $gateway,
        public readonly string $poolName,
    ) {}

    public static function fromModel(Address $address): self
    {
        return new self(
            id: $address->id,
            ip: $address->ip,
            prefixLength: $address->prefix_length,
            version: $address->version,
            gateway: $address->gateway,
            poolName: $address->addressBlock->addressBlockGroup->name,
        );
    }
}
