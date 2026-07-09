<?php

namespace App\Data\Node;

use App\Models\NetworkInterface;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class NetworkInterfaceData extends Data
{
    public function __construct(
        public int $id,
        public int $nodeId,
        public string $name,
        public ?string $description,
        public bool $isVlanAware,
        public ?int $vlanTag,
        #[LoadRelation]
        public Lazy|NodeData $node,
    ) {}

    public static function fromModel(NetworkInterface $interface): self
    {
        return new self(
            id: $interface->id,
            nodeId: $interface->node_id,
            name: $interface->name,
            description: $interface->description,
            isVlanAware: $interface->is_vlan_aware,
            vlanTag: $interface->vlan_tag,
            node: Lazy::whenLoaded(
                'node',
                $interface,
                fn () => NodeData::from($interface->node),
            ),
        );
    }
}
