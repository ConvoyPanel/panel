<?php

namespace App\Data\Ipam;

use App\Data\Server\ServerData;
use App\Enums\Network\AddressVersion;
use App\Models\Address;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class IpamAddressData extends Data
{
    public function __construct(
        public int $id,
        public int $addressBlockId,
        public ?int $serverId,
        public AddressVersion $version,
        public string $ip,
        public int $prefixLength,
        public ?string $gateway,
        public ?string $macAddress,
        #[LoadRelation]
        public Lazy|ServerData|null $server,
        #[LoadRelation]
        public Lazy|AddressBlockData $addressBlock,
    ) {}

    public static function fromModel(Address $address): self
    {
        return new self(
            id: $address->id,
            addressBlockId: $address->address_block_id,
            serverId: $address->server_id,
            version: $address->version,
            ip: $address->ip,
            prefixLength: $address->prefix_length,
            gateway: $address->gateway,
            macAddress: $address->mac_address,
            server: Lazy::whenLoaded(
                'server',
                $address,
                fn () => $address->server
                    ? ServerData::from($address->server)
                    : null,
            ),
            addressBlock: Lazy::whenLoaded(
                'addressBlock',
                $address,
                fn () => AddressBlockData::from($address->addressBlock),
            ),
        );
    }
}
