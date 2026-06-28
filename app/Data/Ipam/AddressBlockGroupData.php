<?php

namespace App\Data\Ipam;

use App\Models\AddressBlockGroup;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class AddressBlockGroupData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public int $addressBlocksCount,
        public int $nodesCount,
    ) {}

    public static function fromModel(AddressBlockGroup $group): self
    {
        return new self(
            id: $group->id,
            name: $group->name,
            description: $group->description,
            addressBlocksCount: (int) ($group->address_blocks_count ?? 0),
            nodesCount: (int) ($group->nodes_count ?? 0),
        );
    }
}
