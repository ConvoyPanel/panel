<?php

namespace App\Data\Ipam;

use App\Enums\Network\AddressVersion;
use App\Models\AddressBlock;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class AddressBlockData extends Data
{
    public function __construct(
        public int $id,
        public int $addressBlockGroupId,
        public ?string $name,
        public ?string $description,
        public AddressVersion $version,
        public string $baseIp,
        public ?string $gateway,
        public ?string $macAddress,
        public int $prefixLengthFrom,
        public int $prefixLengthTo,
    ) {}

    public static function fromModel(AddressBlock $block): self
    {
        return new self(
            id: $block->id,
            addressBlockGroupId: $block->address_block_group_id,
            name: $block->name,
            description: $block->description,
            version: $block->version,
            baseIp: $block->base_ip,
            gateway: $block->gateway,
            macAddress: $block->mac_address,
            prefixLengthFrom: $block->prefix_length_from,
            prefixLengthTo: $block->prefix_length_to,
        );
    }
}
