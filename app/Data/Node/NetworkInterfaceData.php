<?php

namespace App\Data\Node;

use App\Models\NetworkInterface;
use App\Models\Vlan;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
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
        public int $serversCount,
        public int $addressPoolsCount,
        /**
         * Every VLAN on this bridge — declared or merely in use — with the
         * servers resolving to each. Always present rather than lazy: the
         * client merges write responses straight into its cached list, so an
         * absent value would blank the tree until the next refetch.
         *
         * @var DataCollection<int, VlanData>
         */
        public DataCollection $vlans,
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
            serversCount: (int) ($interface->servers_count ?? 0),
            addressPoolsCount: (int) ($interface->address_block_groups_count ?? 0),
            // Explicitly unwrapped: returned inside a collection the nested
            // VLANs serialize as a bare array, but returned as a single
            // resource they would pick up the global `data` wrap and arrive as
            // `vlans.data`. The client merges write responses into the list it
            // got from index, so the two shapes have to agree.
            vlans: VlanData::collect(self::vlansFor($interface), DataCollection::class)
                ->withoutWrapping(),
            node: Lazy::whenLoaded(
                'node',
                $interface,
                fn () => NodeData::from($interface->node),
            ),
        );
    }

    /**
     * Declared VLANs merged with the tags actually in use, tag-ascending.
     *
     * The two sets overlap but neither contains the other: a trunk can be
     * configured with VLANs nothing sits on yet, and a server can carry a tag
     * that was never declared. Dropping either would make the tree lie.
     *
     * @return VlanData[]
     */
    private static function vlansFor(NetworkInterface $interface): array
    {
        if (! $interface->is_vlan_aware) {
            return [];
        }

        $usage = $interface->vlanUsage();
        $declared = $interface->relationLoaded('vlans')
            ? $interface->vlans
            : $interface->vlans()->get();

        return $declared
            ->map(function (Vlan $vlan) use ($usage) {
                $vlan->servers_count = (int) $usage->get($vlan->tag, 0);

                return VlanData::from($vlan);
            })
            ->concat(
                $usage->keys()
                    ->diff($declared->pluck('tag'))
                    ->map(fn (int $tag) => VlanData::undeclared(
                        $interface->id,
                        $tag,
                        $usage->get($tag),
                    )),
            )
            ->sortBy('tag')
            ->values()
            ->all();
    }
}
