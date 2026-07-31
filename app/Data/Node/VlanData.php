<?php

namespace App\Data\Node;

use App\Models\Vlan;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class VlanData extends Data
{
    public function __construct(
        /**
         * Null for a VLAN that is in use but was never declared — a server
         * carries the tag, but no `vlans` row describes it. Nothing stops that
         * today (server tags are validated for range and bridge awareness, not
         * against the registry), and hiding those would hide the servers on
         * them. Such a VLAN can't be renamed or deleted until it's declared.
         */
        public ?int $id,
        public int $networkInterfaceId,
        public int $tag,
        public ?string $name,
        public ?string $description,
        public int $serversCount,
    ) {}

    public static function fromModel(Vlan $vlan): self
    {
        return new self(
            id: $vlan->id,
            networkInterfaceId: $vlan->network_interface_id,
            tag: $vlan->tag,
            name: $vlan->name,
            description: $vlan->description,
            serversCount: (int) ($vlan->servers_count ?? 0),
        );
    }

    public static function undeclared(int $networkInterfaceId, int $tag, int $serversCount): self
    {
        return new self(
            id: null,
            networkInterfaceId: $networkInterfaceId,
            tag: $tag,
            name: null,
            description: null,
            serversCount: $serversCount,
        );
    }
}
